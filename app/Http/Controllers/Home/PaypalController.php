<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Exception\PayPalConnectionException;
use Illuminate\Http\Request;

class PaypalController extends Controller
{
    private $paypal;

    /**
     * 初始化 paypal 验证用户（收款方）信息
     */
    private function startPayPal()
    {
        $this->paypal = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        'AUbuInfjIOXtVsKabU_YQCgvNe6kuTpYaAfw6vnM7qgg4Ty6sOG5nZmrc_ufMPptZcIl2TjegxUW3xHZ',
        'EHrKyriTubpLAXbngYFUp9lhy5JKNgoPcw3nlqwTlj89UWx41akSPBKRq5mo-XGuvbxH3TJ9k-zOV-Fy'
    )
);
    }

    /**
     * 获取表单数据，验证并支付
     * @return paypal的支付页面
     */
    public function pay(Request $request)
    {
        $this -> startPayPal();
        $product = $request->product;
        $price = $request->price;

        if (!$product || !$price) {
            dd('Parameter Error');
        }
        $quantity = 1;
        $shipping = 0;

        $total = $price + $shipping;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName($product)
            ->setCurrency('USD')
            ->setQuantity($quantity)
            ->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems([$item]);

        $details = new Details();
        $details->setShipping($shipping)
            ->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($total)
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("支付描述内容")
            ->setInvoiceNumber(uniqid());

        //填写支付成功时的返回跳转url和支付失败时的返回跳转url
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl('http://paytest.test/redirect?success=true')
            ->setCancelUrl('http://paytest.test/redirect?success=false');

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        try {
            $payment->create($this->paypal);  //错误点

        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }
        $approvalUrl = $payment->getApprovalLink();
        //跳转到paypal支付页面
        return redirect($approvalUrl);
    }

    /**
     * 支付成功或支付失败返回跳转地址
     */
    public function redirect(Request $request)
    {
        $this -> startPayPal();

        $success = $request->success;
        $paymentid = $request->paymentId;
        $payerid = $request->PayerID;
        if(!isset($success) || !isset($paymentid) || !isset($payerid)){
            dd('Parameter Error');
        }

        if((bool)$success === 'false'){
            dd('Transaction cancelled!');
        }

        $payment = Payment::get($paymentid, $this->paypal);

        $execute = new PaymentExecution();
        $execute->setPayerId($payerid);

        try{
            $result = $payment->execute($execute, $this->paypal);
        }catch(Exception $e){
            dd($e);
        }
        echo 'SUCCESS! Thank You!';
    }
}