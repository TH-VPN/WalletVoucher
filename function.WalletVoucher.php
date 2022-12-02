<?php
#
# วิธีใช้งานแบบง่ายที่สุด
# isVoucherUsed($url, $mobile, $name) เช็คว่าถูกใช้งานหรือยัง / ตรวจลิ้งค์ (FALSE = ยังไม่ได้ใช้ , TRUE = ใช้แล้ว)
# VoucherVerify($url) สำหรับตรวจว่าลิ้งค์ถูกแบบไหม และ เช็คสถานะ แสดงผลเป็น JSON
# VoucherRedeem($url, $mobile) สำหรับการ Redeem Voucher แสดงผลเป็น JSON
# VoucherLinkToHash($url) สำหรับแปลงค่าให้เหลือแค่ Hash
# GetAmountFromResult($result, $mobile, $name) เช็คค่าเงินหลังการเติมเงินที่สถานะ $result(เป็น ARRAY ที่ได้จากการแปลง
#       json_decode จาก ค่า Return VoucherRedeem) SUCCESS
# ValidateURL($url) สำหรับตรวจสอบค่าว่าลิ้งค์เป็นลิ้งค์ไหมตาม FILTER_VAR
#

$url_voucher = "https://gift.truemoney.com/campaign/vouchers/";

function isVoucherUsed($url, $mobile, $name)
{
    $verify = VoucherVerify($url);
    if ($verify) {
        $array = json_decode($verify, true);
        if ($array["status"]["code"] != "VOUCHER_OUT_OF_STOCK" && $array["status"]["code"] != "CANNOT_GET_OWN_VOUCHER") {
            $numset = [mb_substr($mobile, 0, 3), preg_replace("[0-9]", mb_substr($mobile, 3, 3), "xxx"), mb_substr($mobile, 6, 4)];
            $phone_pattern = $numset[0] . "-" . $numset[1] . "-" . $numset[2];
            $mobile_noused = true;
            foreach ($array["data"]["tickets"] as $data) {
                $realname = explode(" ", $data["full_name"]);
                if ($data["mobile"] == $phone_pattern && $name === $realname[0]) {
                    $mobile_noused = false;
                    break;
                }
            }
            if ($array["data"]["voucher"]["available"] >= 1 && $mobile_noused === true) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    } else {
        return true;
    }
}

function VoucherVerify($url)
{
    if (ValidateURL($url)) {
        global $url_voucher;
        $hash = VoucherLinkToHash($url);
        $des = $url_voucher . $hash . "/verify";
        $res = VoucherVerifycURL($des);
        return $res;
    } else {
        return false;
    }
}

function VoucherLinkToHash($url)
{
    $explode = explode("v=", $url);
    if (count($explode) == 2) {
        return preg_replace("[^a-zA-Z0-9]", "", $explode[1]);
    } else {
        return false;
    }
}

function GetAmountFromResult($result, $mobile, $name)
{
    $numset = [mb_substr($mobile, 0, 3), preg_replace("[0-9]", mb_substr($mobile, 3, 3), "xxx"), mb_substr($mobile, 6, 4)];
    $phone_pattern = $numset[0] . "-" . $numset[1] . "-" . $numset[2];
    $amount = false;
    foreach ($result["data"]["tickets"] as $data) {
        $realname = explode(" ", $data["full_name"]);
        if ($data["mobile"] == $phone_pattern && $name === $realname[0]) {
            $number = str_replace(",","", $data["amount_baht"]);
            $amount = (int) floor((float) $number);
            break;
        }
    }
    return $amount;
}

function ValidateURL($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    } else {
        return false;
    }
}

function VoucherRedeem($url, $mobile)
{
    if (ValidateURL($url)) {
        global $url_voucher;
        $hash = VoucherLinkToHash($url);
        $des = $url_voucher . $hash . "/redeem";
        $res = VoucherRedeemcURL($des, json_encode(["mobile" => $mobile, "voucher_hash" => $hash]));
        return $res;
    } else {
        return false;
    }
}

function VoucherVerifycURL($url)
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => "spider",
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_SSL_VERIFYPEER => false
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg  = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    if (!$err) {
        return $content;
    } else {
        return false;
    }
}

function VoucherRedeemcURL($url, $json_data)
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => "spider",
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_SSL_VERIFYPEER => false
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg  = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    if (!$err) {
        return $content;
    } else {
        return false;
    }
}
