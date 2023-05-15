<?php
//echo "<pre> Line: " . __LINE__ . ", File: " . __FILE__ . "\n";
//print_r($infoEA);
//print_r($inputData);
//print_r($loginRole);
//print_r($loginUser);
//echo "</pre>";
// （公共建築整備課・公共建築保全課用）協議・提出書(1)

require_once("com_str.phl");
require_once("circular/crcl_rep_vwfunc2.phl");
global $signsInfo_pdf;

$n_initiation_type = array(MINUTE_INITTYPE_INSTRUCT => false, MINUTE_INITTYPE_AGREE => false
, MINUTE_INITTYPE_CONFER => false, MINUTE_INITTYPE_PRESENT => false
, MINUTE_INITTYPE_REPORT => false);

$n_response_type = array(MINUTE_RESPONSE_CONSENT => false, MINUTE_RESPONSE_AGREE => false
, MINUTE_RESPONSE_INSTRUCT => false, MINUTE_RESPONSE_ACCEPT => false);

// 起案画面表示時のデータ取得
if (($act_code == AC_SHOW_START_CIRCULAR) && !$rcExec && !$befMkFlg) {
	$inputData["N_PROPONENT_TYPE"] = $loginRole["N_PROPONENT_TYPE"];
	if (($inputData["N_PROPONENT_TYPE"] != ROLE_UKEOISHA)
		&& ($inputData["N_PROPONENT_TYPE"] != ROLE_HACHUSHA)) {
		$inputData["N_PROPONENT_TYPE"] = ROLE_UKEOISHA;
	}
}

if ($infoEA['x_18']) {
	$infoEA[BCODE_RESPONSE_DATE] = false;
	$infoEA[BCODE_RESPONSE_TYPE] = false;
	$infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL] = false;
}

if ($allEditFlg || $mdlPEExec) {
	$infoEA[BCODE_RESPONSE_DATE] = true;
	$infoEA[BCODE_RESPONSE_TYPE] = true;
	$infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL] = true;
}
if ($initConfExec) {
	$infoEA[BCODE_PROPOSE_DATE] = true;
	$infoEA[BCODE_RESPONSE_DATE] = true;
}

// ワンレス支援からの遷移の場合、ワンレス情報から情報を取得する。
$odrStartFlg = false;
$odrTitle = "";
if (($odrExec != "") && ($comKind == CK_COM_MAKE)) {
	$odrStartFlg = true;
	$allEditFlg = true;
	require_once("oneday/onedayDef.phl");
	$detInfo = $odrInfo["ODR_DETAIL"];
	$mykInfo = $odrInfo["ODR_MYK"];
	$kuusyu = $odrInfo[ODR_TITLE];
	$odrTitle = $mykInfo[ODR_TITLE];
	$minutes_detail = "";
	// どちらでもない場合は受注者とみなす
	if (($inputData["N_PROPONENT_TYPE"] == "")
		&& !array_key_exists("N_PROPONENT_TYPE", $loginRole)) {
		$inputData["N_PROPONENT_TYPE"] = ROLE_UKEOISHA;
	}
	if (is_array($detInfo)) {
		$firstFlg = true;
		$firstUKID = "";
		$insDT = "";
		$docTemp = "";
		$kaitoRes = "";
		$kaitoDT = "";
		foreach ($detInfo as $messInfo) {
			$docs = $messInfo["DOCS"];
			if (!is_array($docs)) $docs = array();
			if ($firstFlg) {
				if ($firstUKID == "") {
					$firstUKID = $messInfo["CKID"] . $messInfo["CUID"];
					$insDT = $messInfo["D_UPDATE_DT"];
				}
				if ($firstUKID == $messInfo["CKID"] . $messInfo["CUID"]) $firstFlg = false;
			}
			foreach ($docs as $doc) {
				if ($docTemp != "") $docTemp .= ", ";
				$docTemp .= $doc["C_FILE_NAME"];
			}
			switch ($messInfo["C_KIND"]) {
				// 協議
				case 0:
					// 完了
				case 3:
					if ($minutes_detail != "") $minutes_detail .= "\r\n";
					$minutes_detail .= $messInfo["C_MESSAGE"];
					break;
				// 回答
				case 1:
					if ($kaitoRes != "") $kaitoRes .= "\r\n";
					$kaitoRes .= $messInfo["C_MESSAGE"];
					$kaitoDT = $messInfo["D_UPDATE_DT"];
					break;
				// 回答期限...おそらく不要だが念のため
				case 2:
					if ($kaitoRes != "") $kaitoRes .= "\r\n";
					$kaitoRes .= $messInfo["C_MESSAGE"];
					break;
			}
		}
		$inputData["C_MINUTES_DETAIL"] = $minutes_detail;
		$inputData["C_ATTACHED_DOCUMENT"] = $docTemp;
		$inputData["D_RESPONSE_DATE"] = $kaitoDT;
		$inputData["C_RESPONSE_TYPE_OTHER_DETAIL"] = $kaitoRes;
		$inputData["D_PROPOSE_DATE"] = $insDT;
		// 発議者が受注者でない
		if ($inputData["N_PROPONENT_TYPE"] != ROLE_UKEOISHA) {
			$inputData["N_RESPONSE_CATEGORY"] = ROLE_UKEOISHA;
		} else {
			$inputData["N_RESPONSE_CATEGORY"] = ROLE_HACHUSHA;
		}
	}
} else if ($peExec) {
	$allEditFlg = true;
}

// 発議者区分
if ($inputData["N_RESPONSE_TYPE"] != "") {
	$n_response_type[$inputData["N_RESPONSE_TYPE"]] = " checked";
}

// 発議事項
if ($inputData["N_INITIATION_TYPE"] != "") {
	$n_initiation_type[$inputData["N_INITIATION_TYPE"]] = " checked";
}

// 発議年月日
$hatsugidate = CMainFuncCtrl::circularDateSet(
	$inputData["D_PROPOSE_DATE"],
	$infoEA[BCODE_PROPOSE_DATE],
	['name' => CMainFuncCtrl::setNengoString(PN_REPMINUTE_PROP_DATE)]
);

// 工事名
$x_1_id = "";
$x_1 = "";
if (is_array($inputExData["x_1"]) && (count($inputExData["x_1"]) > 0)) {
	$x_1 = $inputExData["x_1"][0]["C_INPUT_CONTENT"];
	$x_1_id = $inputExData["x_1"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	$input_list = array(
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_KOJIMEI
		),
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES_CONFER
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_KOJIMEI
		),
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES_INSTRUCT
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_KOJIMEI
		),
	);
	// 最後に発議した帳票入力値より「工事名称」の入力値を取得する
	if (getIwateReportInputContentsByInputMasters($ora, $input_list, $kaishaID, $genbaID, $latestInfo)) {
		if (is_array($latestInfo) && count($latestInfo) > 0 && $latestInfo[0]["C_INPUT_CONTENT"] != "") {
			$x_1 = $latestInfo[0]["C_INPUT_CONTENT"];
		}
	}
	// 上記で未取得の場合、現場情報から取得
	if ($x_1 == "") {
		$x_1 = $genbaInfo["genba_name"];
	}
}

// 宛先役割
$x_2_id = "";
$x_2 = "";
if (is_array($inputExData["x_2"]) && (count($inputExData["x_2"]) > 0)) {
	$x_2 = $inputExData["x_2"][0]["C_INPUT_CONTENT"];
	$x_2_id = $inputExData["x_2"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	// 打合簿,履行報告で最後に発議した帳票の入力値を取得
	$x_2 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "2");
	// 発議済み情報から取得できない場合は現場情報から取得
	if ($x_2 == "") $x_2 = $genbaInfo["genba_cd"];
}

// 受注者
$x_3_id = "";
$x_3 = "";
if (is_array($inputExData["x_3"]) && (count($inputExData["x_3"]) > 0)) {
	$x_3 = $inputExData["x_3"][0]["C_INPUT_CONTENT"];
	$x_3_id = $inputExData["x_3"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	// 打合簿,履行報告で最後に発議した帳票の入力値を取得
	$x_3 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "3");
	// 発議済み情報から取得できない場合は現場情報から取得
	if ($x_3 == "") $x_3 = $genbaInfo["ukeoisha"];
	// 現場情報から取得できない、且つ受注者発議の場合はログインユーザーの所属会社名を取得
	if ($x_3 == "" && $inputData["N_PROPONENT_TYPE"] == ROLE_UKEOISHA) $x_3 = $loginUser["C_HOJIN_NAME"];
}

// 発議者役割
$x_5 = "";
$x_5_id = "";
if (is_array($inputExData["x_5"]) && (count($inputExData["x_5"]) > 0)) {
	$x_5 = $inputExData["x_5"][0]["C_INPUT_CONTENT"];
	$x_5_id = $inputExData["x_5"][0]["N_REPORT_DETAIL_ID"];
}
// 工事場所
$x_6 = "";
$x_6_id = "";

if (is_array($inputExData["x_6"]) && (count($inputExData["x_6"]) > 0)) {
	$x_6 = $inputExData["x_6"][0]["C_INPUT_CONTENT"];
	$x_6_id = $inputExData["x_6"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	$input_list = array(
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_ADDRESS
		),
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES_CONFER
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_ADDRESS
		),
		array("N_REPORT_REGION_ID" => CRCL_REGID_GIFUSHI
		, "N_REPORT_MASTER_ID" => CRCL_MASID_GIFUSHI_MINUTES_INSTRUCT
		, "N_REPORT_INPUT_MASTER_ID" => GIFUSHI_MSTRID_MINUTES_INSTRUCT_ADDRESS
		),
	);
	// 最後に発議した帳票入力値より「工事名称」の入力値を取得する
	if (getIwateReportInputContentsByInputMasters($ora, $input_list, $kaishaID, $genbaID, $latestInfo)) {
		if (is_array($latestInfo) && count($latestInfo) > 0 && $latestInfo[0]["C_INPUT_CONTENT"] != "") {
			$x_6 = $latestInfo[0]["C_INPUT_CONTENT"];
		}
	}
	// 上記で未取得の場合、現場情報から取得
	if ($x_6 == "") {
		$x_6 = $_POST["basyo_gifu_" . $kaishaID . $genbaID];
		if ($x_6 == "") $x_6 = $genbaInfo["genba_plc"];
	}
}

$x_7 = "";
$x_7_id = "";
if (is_array($inputExData["x_7"]) && (count($inputExData["x_7"]) > 0)) {
	$x_7 = $inputExData["x_7"][0]["C_INPUT_CONTENT"];
	$x_7_id = $inputExData["x_7"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind = CK_COM_MAKE) {
	$chkType = $defRoutes[0]["N_PROPONENT_TYPE"];    //発議者の役割区分
	for ($rCnt = 1; $rCnt < count($defRoutes); $rCnt++) {
		if ($chkType != $defRoutes[$rCnt]["N_PROPONENT_TYPE"]) {
			$x_7 = $defRoutes[$rCnt]["users"][0]["C_SHAIN_NAME"];
			break;
		}
	}
}

// 契約番号
$x_8 = "";
$x_8_id = "";
if (is_array($inputExData["x_8"]) && (count($inputExData["x_8"]) > 0)) {
	$x_8 = $inputExData["x_8"][0]["C_INPUT_CONTENT"];
	$x_8_id = $inputExData["x_8"][0]["N_REPORT_DETAIL_ID"];
}

// 発議者氏名
$x_10 = "";
$x_10_id = "";
if (is_array($inputExData["x_10"]) && (count($inputExData["x_10"]) > 0)) {
	$x_10 = $inputExData["x_10"][0]["C_INPUT_CONTENT"];
	$x_10_id = $inputExData["x_10"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	$x_10 = $loginUser["C_SHAIN_NAME"];
}


//工期-開始日
$x_11_date = '';
$x_11_id = "";
if (is_array($inputExData["x_11"]) && (count($inputExData["x_11"]) > 0)) {
	$x_11_date = $inputExData["x_11"][0]["C_INPUT_CONTENT"];
	$x_11_id = $inputExData["x_11"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	// 現場情報の「施工開始日」を取得
	$x_11_date = $genbaInfo["gstart"];
}

$x_11 = CMainFuncCtrl::circularDateSet(
	$x_11_date,
	$infoEA['x_11'],
	['name' => CMainFuncCtrl::setNengoString('x_11')]
);

//工期-終了日
$x_12_date = '';
$x_12_id = '';
if (is_array($inputExData["x_12"]) && (count($inputExData["x_12"]) > 0)) {
	$x_12_date = $inputExData["x_12"][0]["C_INPUT_CONTENT"];
	$x_12_id = $inputExData["x_12"][0]["N_REPORT_DETAIL_ID"];
} else if ($comKind == CK_COM_MAKE) {
	// 現場情報の「施工終了日」を取得
	$x_12_date = $genbaInfo["gend"];
}
$x_12 = CMainFuncCtrl::circularDateSet(
	$x_12_date,
	$infoEA['x_12'],
	['name' => CMainFuncCtrl::setNengoString('x_12')]
);

//工事着手日
$x_13_date = '';
$x_13_id = '';
if (is_array($inputExData["x_13"]) && (count($inputExData["x_13"]) > 0)) {
	$x_13_date = $inputExData["x_13"][0]["C_INPUT_CONTENT"];
	$x_13_id = $inputExData["x_13"][0]["N_REPORT_DETAIL_ID"];
}
$x_13 = CMainFuncCtrl::circularDateSet(
	$x_13_date,
	$infoEA['x_13'],
	['name' => CMainFuncCtrl::setNengoString('x_13')]
);

// 監理事務所名
$x_14 = '';
$x_14_id = '';
if (is_array($inputExData["x_14"]) && (count($inputExData["x_14"]) > 0)) {
	$x_14 = $inputExData["x_14"][0]["C_INPUT_CONTENT"];
	$x_14_id = $inputExData["x_14"][0]["N_REPORT_DETAIL_ID"];
}

// 建築立会人
$x_15 = '';
$x_15_id = '';
if (is_array($inputExData["x_15"]) && (count($inputExData["x_15"]) > 0)) {
	$x_15 = $inputExData["x_15"][0]["C_INPUT_CONTENT"];
	$x_15_id = $inputExData["x_15"][0]["N_REPORT_DETAIL_ID"];
}

// 電気立会人
$x_16 = '';
$x_16_id = '';
if (is_array($inputExData["x_16"]) && (count($inputExData["x_16"]) > 0)) {
	$x_16 = $inputExData["x_16"][0]["C_INPUT_CONTENT"];
	$x_16_id = $inputExData["x_16"][0]["N_REPORT_DETAIL_ID"];
}

// 機械立会人
$x_17 = '';
$x_17_id = '';
if (is_array($inputExData["x_17"]) && (count($inputExData["x_17"]) > 0)) {
	$x_17 = $inputExData["x_17"][0]["C_INPUT_CONTENT"];
	$x_17_id = $inputExData["x_17"][0]["N_REPORT_DETAIL_ID"];
}

//指示日
$x_18_date = '';
$x_18_id = '';
if (is_array($inputExData["x_18"]) && (count($inputExData["x_18"]) > 0)) {
	$x_18_date = $inputExData["x_18"][0]["C_INPUT_CONTENT"];
	$x_18_id = $inputExData["x_18"][0]["N_REPORT_DETAIL_ID"];
}
$x_18 = CMainFuncCtrl::circularDateSet(
	$x_18_date,
	$infoEA['x_18'],
	['name' => CMainFuncCtrl::setNengoString('x_18')]
);

// 処理・回答年月日(回答)
$d_response_date = CMainFuncCtrl::circularDateSet(
	$inputData["D_RESPONSE_DATE"],
	$infoEA[BCODE_RESPONSE_DATE],
	['name' => CMainFuncCtrl::setNengoString(PN_REPMINUTE_RESP_DATE)]
);

?>
<style type="text/css">

    .text-fixed {
        position: relative;
        top: 10px;
    }

    .x5, .x10 {
        padding-left: 2px;
    }

    .x14, .x15, .x16, .x17 {
        padding-left: 10px;
    }

    .func-tbl td {
        padding: 5px 0;
    }

    .align-title {
        display: flex;
        text-align: right;
        justify-content: center;
    }

    .x3 {
        width: 145px;
        padding: 5px 10px;
    }

    .td-x1 {
        /*width: 245px;*/
    }

    #x_8 {
        width: 120px;
    }

    .x6, .x8 {
        padding-left: 5px;
    }

    .x2, .x7 {
        padding-left: 10px;
    }

    .x1 {
        padding: 5px 10px;
        width: 225px;
    }

    .td-x8-title {
        width: 70px;
    }

    #x_1 {
        width: 210px;
    }

    <!--
    input.disable {
        border: 1px solid #C9CCD4;
        background-color: #ECE9D8;
    }

    input.editable {
        border: 1px solid #7F9DB9;
        background-color: transparent;
    }

    input, textarea {
        font: normal 12px /1.3em "ＭＳ ゴシック", Osaka, sans-serif;
        color: #3f3f3f;
    }

    input {
        height: 14px;
    }

    textarea {
    <?php if (!isFireFox()) { ?> overflow-x: hidden;
    <?php } ?> overflow-y: scroll;
        resize: none;
    }

    table tr td input.radio {
        margin-top: -5px;
    }

    table#uchiawasenaiyo1 tr td.formno {
        border-bottom: none;
        height: 1.8em;
    }

    table#uchiawasenaiyo1 tr td.formname {
        border-top: none;
        border-bottom: none;
        text-align: center;
        font-size: x-large;
    }

    table td .labelr {
        margin-right: 1em;
    }

    /*table td.r {*/
    /*	width: 25%;*/
    /*}*/
    table td.r label {
        margin-right: 2em;
    }

    .initTypeSet {
        margin: 1em 0 1em 1em;
    }

    .m_detail {
        width: 520px;
        height: 240px;
        font: normal 12px /1.3em "ＭＳ ゴシック", Osaka, sans-serif;
        margin: 4px 0 4px 20px;
    }

    #divbody table td table.noneborder td,
    #report_space table td table.noneborder td {
        border: none;
    }

    td.nochk {
        vertical-align: bottom;
    }

    #divbody table td table.noneborder td.kakko,
    #report_space table td table.noneborder td.kakko {
        padding: 0;
        margin: 0;
        vertical-align: bottom;
    }

    .kakko img {
        float: left;
    }

    .resp_detail {
        width: 520px;
        height: 240px;
        font: normal 12px /1.3em "ＭＳ ゴシック", Osaka, sans-serif;
        margin: 0 0 0 20px;
    }

    table th.tate2, table th.tate {
        border: 1px solid #919191;
        text-align: center;
        background: #78808f;
        color: #ffffff;
    }

    table#uchiawasenaiyo1 tr td table tr.smallrow td, table#uchiawasenaiyo1 tr td table tr.largerow td.kakko {
        padding-top: 10px;
    }

    table#uchiawasenaiyo1 tr td table tr.smallrow td {
        padding-bottom: 5px;
    }

    table.sign_var .ttl_blnk td {
        text-align: right;
        width: auto;
    }

    table tr td table tr.smallrow td.chkfirst {
        width: 105px;
    }

    .route_btn {
        float: right;
        margin-bottom: 1em;
    }

    .titleSpan {
        padding-left: 10px;
    }

    .titleDiv {
        width: 510px;
        padding-left: 10px;
        text-align: center;
        font: normal 12px /1.3em "ＭＳ ゴシック", Osaka, sans-serif;
    }

    textarea {
        overflow-x: hidden;
        overflow-y: scroll;
        resize: none;
    }

    #x_2, #x_7, #x_15, #x_16, #x_17 {
        width: 120px;
    }

    #x_5, #x_10 {
        width: 240px;
    }

    .x10, .x7 {
        margin-top: 6px;
    }

    input#x_6 {
        width: 480px;
    }

    input#x_14 {
        width: 455px;
    }

    div.kojimei {
        width: 484px;
        font: normal 12px /1.3em "ＭＳ ゴシック", Osaka, sans-serif;
    }

    table#uchiawasenaiyo1 tr td.embSignArea {
        border-top: none;
        border-bottom: none;
    }

    .signArea {
        float: right;
        margin: 10px 4px 10px 0;
        height: 160px;
    }

    .clear {
        clear: both;
    }

    #text_atesaki {
        padding-left: 10px;
    }

    #select_prop {
        border-right: none;
        padding-left: 20px;
    }

    #input_prop {
        border-left: none;
    }

    #text_sKoki, #text_eKoki {
        width: 50px;
        height: 28px;
        text-align: center;
        border-right: none;
    }

    #date_sKoki, #date_eKoki {
        border-left: none;
    }

    #text_sKoki, #date_sKoki {
        border-bottom: none;
    }

    #text_eKoki, #date_eKoki {
        border-top: none;
    }

    #comments tr td {
        border: none;
    }

    .lineThrough {
        text-decoration: line-through underline;
    }

    #row_propType {
        height: 32px;
        text-align: center;
    }

    .hide {
        display: none;
    }

    select.form-input-nengo {
        height: 20px;
        vertical-align: bottom;
    }

    .x13 {
        display: inline-block;
        margin-left: 200px;
    }


    .func-tbl td.input-title, .in-func-tbl td.input-title {
        width: auto;
    }

    .td-x15-title.input-title {
        width: 65px;
    }

    -->
</style>
<?php
//JavaScript非出力指定時以外は出力する。
if (!$jsNoneFlg) {
	?>
    <script type="text/javascript">
        <!--

        function setPDFParam(fromfrm, sendfrm, chkFlg) {
	
			<?php if ($infoEA[BCODE_PROPOSE_DATE]): ?>
            // 発議年月日
            var check_name = '<?= PN_REPMINUTE_PROP_DATE ?>';
            var check_object = createCheckDateObject(fromfrm, check_name, '発議年月日');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>
	
			<?php if ($infoEA["x_11"]): ?>
            // 工期-開始日
            var check_name = 'x_11';
            var check_object = createCheckDateObject(fromfrm, check_name, '工期-開始日');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>
	
			<?php if ($infoEA["x_12"]): ?>
            var check_name = 'x_12';
            var check_object = createCheckDateObject(fromfrm, check_name, '工期-終了日');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>
	
			<?php if ($infoEA["x_13"]): ?>
            var check_name = 'x_13';
            var check_object = createCheckDateObject(fromfrm, check_name, '工事着手日');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>
	
			<?php if ($infoEA["x_18"]): ?>
            var check_name = 'x_18';
            var check_object = createCheckDateObject(fromfrm, check_name, '指示日');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>
	
			<?php if ($infoEA[BCODE_RESPONSE_DATE]): ?>
            var check_name = '<?= PN_REPMINUTE_RESP_DATE ?>';
            var check_object = createCheckDateObject(fromfrm, check_name, '処理・回答年月日(回答)');
            if (invalidFormDate(check_object)) return false;
			<?php endif; ?>

            if (!chkFlg) {
                // 内容
                if (checkElemType(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, "textarea")) {
                    jsHanToZen(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>);
                    if (!checkTextAreaSize(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, 86, 13)) {
                        if (!confirm("内容 の入力数が多い為、PDFに表示されない文字があります。")) {
                            alert("1行は全角43文字(半角86文字)まで、行数は13行までで入力して"
                                + "ください。");
                            fromfrm
                        .<?php echo(PN_REPMINUTE_DETAIL); ?>.
                            focus();
                            return false;
                        }
                    }
                }
                // 処理・回答　その他　内容(回答)
                if (checkElemType(fromfrm.c_response_type_other_detail, "textarea")) {
                    jsHanToZen(fromfrm.c_response_type_other_detail);
                    if (!checkTextAreaSize(fromfrm.c_response_type_other_detail, 86, 13)) {
                        if (!confirm("処理・回答　その他　内容(回答) の入力数が多い為、PDFに表示されない文字があります。")) {
                            alert("1行は全角43文字(半角86文字)まで、行数は13行までで入力して"
                                + "ください。");
                            fromfrm.c_response_type_other_detail.focus();
                            return false;
                        }
                    }
                }

                // 工事名
                if (checkElemType(fromfrm.x_1, "textarea")) {
                    jsHanToZen(fromfrm.x_1);
                    if (!checkTextAreaSize(fromfrm.x_1, 32, 5)) {
                        if (!confirm("工事名 の入力数が多い為、PDFに表示されない文字があります。")) {
                            alert("1行は全角16文字(半角32文字)まで、行数は5行までで入力して"
                                + "ください。");
                            fromfrm.x_1.focus();
                            return false;
                        }
                    }
                }

                // 宛先役割
                if (checkElemType(fromfrm.x_2, "textarea")) {
                    jsHanToZen(fromfrm.x_2);
                    if (!checkTextAreaSize(fromfrm.x_2, 20, 1)) {
                        if (!confirm("宛先役割 の入力数が多い為、PDFに表示されない文字があります。")) {
                            alert("1行は全角10文字(半角20文字)まで、行数は1行までで入力して"
                                + "ください。");
                            fromfrm.x_2.focus();
                            return false;
                        }
                    }
                }

                // 受注者
                if (checkElemType(fromfrm.x_3, "textarea")) {
                    jsHanToZen(fromfrm.x_3);
                    if (!checkTextAreaSize(fromfrm.x_3, 20, 3)) {
                        if (!confirm("受注者 の入力数が多い為、PDFに表示されない文字があります。")) {
                            alert("1行は全角10文字(半角20文字)まで、行数は3行までで入力して"
                                + "ください。");
                            fromfrm.x_3.focus();
                            return false;
                        }
                    }
                }
            } else {
                // 内容
                if (checkElemType(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, "textarea")) {
                    jsHanToZen(fromfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>);
                    if (!chkTextAreaSize(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, 86, 13, "内容")) {
                        fromfrm
                    .<?php echo(PN_REPMINUTE_DETAIL); ?>.
                        focus();
                        return false;
                    }
                }

                //処理・回答　その他　内容(回答)
                if (checkElemType(fromfrm.c_response_type_other_detail, "textarea")) {
                    jsHanToZen(fromfrm.c_response_type_other_detail);
                    if (!chkTextAreaSize(fromfrm.c_response_type_other_detail, 86, 13, "処理・回答内容")) {
                        fromfrm.c_response_type_other_detail.focus();
                        return false;
                    }
                }

                // 工事名
                if (checkElemType(fromfrm.x_1, "textarea")) {
                    jsHanToZen(fromfrm.x_1);
                    if (!chkTextAreaSize(fromfrm.x_1, 32, 5, "工事名")) {
                        fromfrm.x_1.focus();
                        return false;
                    }
                }

                // 宛先役割
                if (checkElemType(fromfrm.x_2, "textarea")) {
                    jsHanToZen(fromfrm.x_2);
                    if (!chkTextAreaSize(fromfrm.x_2, 20, 1, "宛先役割")) {
                        fromfrm.x_2.focus();
                        return false;
                    }
                }

                // 受注者
                if (checkElemType(fromfrm.x_3, "textarea")) {
                    jsHanToZen(fromfrm.x_3);
                    if (!chkTextAreaSize(fromfrm.x_3, 20, 3, "受注者")) {
                        fromfrm.x_3.focus();
                        return false;
                    }
                }

                // 発議者役割
                if (!checkElemType(fromfrm.x_5, "hidden")) {
                    jsHanToZen(fromfrm.x_5);
                    if (InputCheck(fromfrm.x_5, 0, 40, 0, "発議者役割", 6) == false) {
                        fromfrm.x_5.focus();
                        return false;
                    }
                }

                // 工事場所
                if (!checkElemType(fromfrm.x_6, "hidden")) {
                    jsHanToZen(fromfrm.x_6);
                    if (InputCheck(fromfrm.x_6, 0, 80, 0, "工事場所", 6) == false) {
                        fromfrm.x_6.focus();
                        return false;
                    }
                }

                // 宛先
                if (!checkElemType(fromfrm.x_7, "hidden")) {
                    jsHanToZen(fromfrm.x_7);
                    if (InputCheck(fromfrm.x_7, 0, 20, 0, "宛先", 6) == false) {
                        fromfrm.x_7.focus();
                        return false;
                    }
                }

                // 契約番号
                if (!checkElemType(fromfrm.x_8, "hidden")) {
                    jsHanToZen(fromfrm.x_8);
                    if (InputCheck(fromfrm.x_8, 0, 20, 0, "契約番号", 6) == false) {
                        fromfrm.x_8.focus();
                        return false;
                    }
                }

                // 発議者氏名
                if (!checkElemType(fromfrm.x_10, "hidden")) {
                    jsHanToZen(fromfrm.x_10);
                    if (InputCheck(fromfrm.x_10, 0, 40, 0, "発議者氏名", 6) == false) {
                        fromfrm.x_10.focus();
                        return false;
                    }
                }

                // 監理事務所名
                if (!checkElemType(fromfrm.x_14, "hidden")) {
                    jsHanToZen(fromfrm.x_14);
                    if (InputCheck(fromfrm.x_14, 0, 76, 0, "監理事務所名", 6) == false) {
                        fromfrm.x_14.focus();
                        return false;
                    }
                }

                // 建築立会人
                if (!checkElemType(fromfrm.x_15, "hidden")) {
                    jsHanToZen(fromfrm.x_15);
                    if (InputCheck(fromfrm.x_15, 0, 20, 0, "建築立会人", 6) == false) {
                        fromfrm.x_15.focus();
                        return false;
                    }
                }

                // 電気立会人
                if (!checkElemType(fromfrm.x_16, "hidden")) {
                    jsHanToZen(fromfrm.x_16);
                    if (InputCheck(fromfrm.x_16, 0, 20, 0, "電気立会人", 6) == false) {
                        fromfrm.x_16.focus();
                        return false;
                    }
                }

                // 機械立会人
                if (!checkElemType(fromfrm.x_17, "hidden")) {
                    jsHanToZen(fromfrm.x_17);
                    if (InputCheck(fromfrm.x_17, 0, 20, 0, "機械立会人", 6) == false) {
                        fromfrm.x_17.focus();
                        return false;
                    }
                }

                // (比較用)工期 開始日
                var beginDT = createCompareDateObject(fromfrm, 'x_11');
                // (比較用)工期 終了日
                var endDT = createCompareDateObject(fromfrm, 'x_12');
                // 工期 開始日と終了日を比較
                if (compareFormDate(beginDT, endDT)) {
                    alert("工期の終了日に開始日よりも前の日付が入力されています。");
                    fromfrm.x_11_1.focus();
                    return false;
                }

                // 未入力確認
                if (!('<?= $peExec ?>' == '1') && !('<?= $mdlPEExec ?>' == '1') && !('<?= $initConfExec ?>' == '1')) {
                    var execConf = false;
                    var inputList = '';
					<?php if ($infoEA[BCODE_PROPOSE_DATE]): ?>
                    // 発議年月日
                    var $<?= PN_REPMINUTE_PROP_DATE ?> = $(fromfrm).find('[name="<?= PN_REPMINUTE_PROP_DATE ?>"]');
                    if ($<?= PN_REPMINUTE_PROP_DATE ?>.val() == '') {
                        execConf = true;
                        inputList += '\n　発議年月日';
                        if (!focusElement) var focusElement = fromfrm.<?= PN_REPMINUTE_PROP_DATE ?>;
                    }
					<?php endif; ?>
                    // 確認処理
                    if (execConf) {
                        if (!confirm('<?= INPUT_CONF_MESSAGE ?>\n' + inputList)) {
                            focusElement.focus();
                            return false;
                        }
                    }
                }
            }

            if (!this.chkCustomSignColText && this.chkSignColText) {
                if (!chkSignColText(fromfrm, sendfrm, chkFlg)) return false;
            } else if (this.chkCustomSignColText) {
                if (!chkCustomSignColText(fromfrm, sendfrm, chkFlg)) return false;
            }

            // 発議年月日
            var prop_date = $(fromfrm).find('input[name=<?php echo PN_REPMINUTE_PROP_DATE ?>]').val();
            sendfrm
        .<?php echo PN_REPMINUTE_PROP_DATE ?>.
            value = getWarekiFormat(prop_date, 'D');

            // 工期-開始日
            var x11_date = $(fromfrm).find('input[name=x_11]').val();
            sendfrm.x_11.value = getWarekiFormat(x11_date, 'D');

            // 工期-終了日
            var x12_date = $(fromfrm).find('input[name=x_12]').val();
            sendfrm.x_12.value = getWarekiFormat(x12_date, 'D');

            // 工事着手日
            var x13_date = $(fromfrm).find('input[name=x_13]').val();
            sendfrm.x_13.value = getWarekiFormat(x13_date, 'D');

            sendfrm.x_18.value = $(fromfrm).find('input[name=x_18_2]').val() + '月 ' + $(fromfrm).find('input[name=x_18_3]').val() + '日';

            // 処理・回答年月日(回答)
            var res_date = $(fromfrm).find('input[name=<?php echo PN_REPMINUTE_RESP_DATE ?>]').val();
            sendfrm.<?php echo PN_REPMINUTE_RESP_DATE ?>_2.value = getWarekiFormat(res_date, 'D');

            // 発議事項
            sendfrm.n_initiation_type_1.value = "";
            sendfrm.n_initiation_type_2.value = "";
            sendfrm.n_initiation_type_3.value = "";
            sendfrm.n_initiation_type_4.value = "";
            sendfrm.n_initiation_type_5.value = "";
            radioFlg = false;
            var initRepFlg = true;
            try {
                radioFlg = ((fromfrm
            .<?php echo(PN_REPMINUTE_INIT_TYPE); ?>.
                length
            )
            &&
                (checkElemType(fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[0], "checkbox"))
            )
                ;
            } catch (e) {
                radioFlg = false;
            }
            if (radioFlg) {
                var selFlg = true;
                if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[0].checked) {
                    sendfrm.n_initiation_type_1.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[1].checked) {
                    sendfrm.n_initiation_type_2.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[2].checked) {
                    sendfrm.n_initiation_type_3.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[3].checked) {
                    sendfrm.n_initiation_type_4.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[4].checked) {
                    sendfrm.n_initiation_type_5.value = 1;
                } else {
                    selFlg = false;
                }
                if (chkFlg) {
                    if (!selFlg) {
                        alert("発議事項を選択してください。");
                        fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[0].focus();
                        ;
                        return false;
                    }
                }
            } else {
                switch (fromfrm
                    .<?php echo(PN_REPMINUTE_INIT_TYPE); ?>.
                        value
                    )
                    {
                    case
                        "<?php echo(MINUTE_INITTYPE_INSTRUCT); ?>"
                    :
                        sendfrm.n_initiation_type_1.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_AGREE); ?>"
                    :
                        sendfrm.n_initiation_type_2.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_CONFER); ?>"
                    :
                        sendfrm.n_initiation_type_3.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_PRESENT); ?>"
                    :
                        sendfrm.n_initiation_type_4.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_REPORT); ?>"
                    :
                        sendfrm.n_initiation_type_5.value = 1;
                        break;
                    }
            }

            // 発議事項
            sendfrm.n_initiation_type_2_1.value = "";
            sendfrm.n_initiation_type_2_2.value = "";
            sendfrm.n_initiation_type_2_3.value = "";
            sendfrm.n_initiation_type_2_4.value = "";
            sendfrm.n_initiation_type_2_5.value = "";
            radioFlg = false;
            var initRepFlg = true;
            try {
                radioFlg = ((fromfrm
            .<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>.
                length
            )
            &&
                (checkElemType(fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[0], "checkbox"))
            )
                ;
            } catch (e) {
                radioFlg = false;
            }
            if (radioFlg) {
                var selFlg = true;
                if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[0].checked) {
                    sendfrm.n_initiation_type_2_1.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[1].checked) {
                    sendfrm.n_initiation_type_2_2.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[2].checked) {
                    sendfrm.n_initiation_type_2_3.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[3].checked) {
                    sendfrm.n_initiation_type_2_4.value = 1;
                } else if (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[4].checked) {
                    sendfrm.n_initiation_type_2_5.value = 1;
                } else {
                    selFlg = false;
                }
                if (chkFlg) {
                    if (!selFlg) {
                        alert("発議事項を選択してください。");
                        fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[0].focus();
                        ;
                        return false;
                    }
                }
            } else {
                switch (fromfrm
                    .<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>.
                        value
                    )
                    {
                    case
                        "<?php echo(MINUTE_INITTYPE_INSTRUCT); ?>"
                    :
                        sendfrm.n_initiation_type_2_1.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_AGREE); ?>"
                    :
                        sendfrm.n_initiation_type_2_2.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_CONFER); ?>"
                    :
                        sendfrm.n_initiation_type_2_3.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_PRESENT); ?>"
                    :
                        sendfrm.n_initiation_type_2_4.value = 1;
                        break;
                    case
                        "<?php echo(MINUTE_INITTYPE_REPORT); ?>"
                    :
                        sendfrm.n_initiation_type_2_5.value = 1;
                        break;
                    }
            }


            // 内容
            sendfrm
        .<?php echo(PN_REPMINUTE_DETAIL); ?>.
            value = fromfrm
        .<?php echo(PN_REPMINUTE_DETAIL); ?>.
            value;

            // 工事名
            sendfrm.x_1.value = '';
            var rowCnt = getStringRowInTA(fromfrm.x_1.value, "32");
            if (rowCnt == 2) {
                sendfrm.x_1_2.value = fromfrm.x_1.value;
            } else if (rowCnt == 3) {
                sendfrm.x_1_3.value = fromfrm.x_1.value;
            } else if (rowCnt == 4) {
                sendfrm.x_1_4.value = fromfrm.x_1.value;
            } else if (rowCnt == 5) {
                sendfrm.x_1_5.value = fromfrm.x_1.value;
            } else {
                sendfrm.x_1.value = fromfrm.x_1.value;
            }

            // 宛先役割
            sendfrm.x_2.value = fromfrm.x_2.value;

            // 受注者
            sendfrm.x_3.value = '';
            var rowCnt = getStringRowInTA(fromfrm.x_3.value, "20");
            if (rowCnt == 2) {
                sendfrm.x_3_2.value = fromfrm.x_3.value;
            } else if (rowCnt == 3) {
                sendfrm.x_3_3.value = fromfrm.x_3.value;
            } else {
                sendfrm.x_3.value = fromfrm.x_3.value;
            }

            // 発議者役割
            sendfrm.x_5.value = fromfrm.x_5.value;

            // 工事場所
            sendfrm.x_6.value = fromfrm.x_6.value;

            // 宛先
            sendfrm.x_7.value = fromfrm.x_7.value;

            // 契約番号
            sendfrm.x_8.value = fromfrm.x_8.value;

            // 発議者氏名
            sendfrm.x_10.value = fromfrm.x_10.value;

            // 監理事務所名
            sendfrm.x_14.value = fromfrm.x_14.value;

            // 建築立会人
            sendfrm.x_15.value = fromfrm.x_15.value;

            // 電気立会人
            sendfrm.x_16.value = fromfrm.x_16.value;

            // 機械立会人
            sendfrm.x_17.value = fromfrm.x_17.value;

            // 処理・回答　その他　内容(回答)
            sendfrm.c_response_type_other_detail_2.value = fromfrm.c_response_type_other_detail.value;

            // 処理・回答-内容 ※受注者発議
            sendfrm.n_response_type_1.value = "";
            sendfrm.n_response_type_2.value = "";
            sendfrm.n_response_type_3.value = "";
            sendfrm.n_response_type_4.value = "";
            var sel_resptype_1 = "";
            radioFlg = false;
            try {
                radioFlg = ((fromfrm.n_response_type.length)
                    && (checkElemType(fromfrm.n_response_type[0], "checkbox")));
            } catch (e) {
                radioFlg = false;
            }
            if (radioFlg) {
                var selFlg = true;
                if (fromfrm.n_response_type[0].checked) {
                    sendfrm.n_response_type_1.value = 1;
                    // sel_resptype_1 = fromfrm.n_response_type[0].value;
                } else if (fromfrm.n_response_type[1].checked) {
                    sendfrm.n_response_type_2.value = 1;
                    // sel_resptype_1 = fromfrm.response_type_1[1].value;
                } else if (fromfrm.n_response_type[2].checked) {
                    sendfrm.n_response_type_3.value = 1;
                    // sel_resptype_1 = fromfrm.response_type_1[2].value;
                } else if (fromfrm.n_response_type[3].checked) {
                    sendfrm.n_response_type_4.value = 1;
                    // sel_resptype_1 = fromfrm.response_type_1[3].value;
                } else if (!fromfrm.n_response_type[0].disabled) {
                    selFlg = false;
                }
            } else {
                switch (fromfrm.n_response_type.value) {
                    case "<?php echo(MINUTE_RESPONSE_CONSENT); ?>":
                        sendfrm.n_response_type_1.value = 1;
                        break;
                    case "<?php echo(MINUTE_RESPONSE_AGREE); ?>":
                        sendfrm.n_response_type_2.value = 1;
                        break;
                    case "<?php echo(MINUTE_RESPONSE_INSTRUCT); ?>":
                        sendfrm.n_response_type_3.value = 1;
                        break;
                    case "<?php echo(MINUTE_RESPONSE_ACCEPT); ?>":
                        sendfrm.n_response_type_4.value = 1;
                        break;
                }
            }

            if (!this.setCustomSignPdfInfo && this.setSignPdfInfo) setSignPdfInfo(sendfrm);
            else if (this.setCustomSignPdfInfo) setCustomSignPdfInfo(sendfrm);

            var setAttrObj = sendfrm.<?php echo(PN_PDF_SETATTR); ?>;
            if (setAttrObj != "") {
                setAttrStr = setAttrObj.value;
                setAttrObj.value = setAttrStr;
            }

            // 発議事項（上部）
            var initiationType1Str = "";
            if (sendfrm.n_initiation_type_1.value == 1) {
                initiationType1Str = "box_initiation_type_1.visible=true, line_initiation_type_1.visible=false, "
                    + "box_initiation_type_2.visible=false, line_initiation_type_2.visible=true, "
                    + "box_initiation_type_3.visible=false, line_initiation_type_3.visible=true, "
                    + "box_initiation_type_4.visible=false, line_initiation_type_4.visible=true, "
                    + "box_initiation_type_5.visible=false, line_initiation_type_5.visible=true, "
            } else if (sendfrm.n_initiation_type_2.value == 1) {
                initiationType1Str = "box_initiation_type_1.visible=false, line_initiation_type_1.visible=true, "
                    + "box_initiation_type_2.visible=true, line_initiation_type_2.visible=false, "
                    + "box_initiation_type_3.visible=false, line_initiation_type_3.visible=true, "
                    + "box_initiation_type_4.visible=false, line_initiation_type_4.visible=true, "
                    + "box_initiation_type_5.visible=false, line_initiation_type_5.visible=true, "
            } else if (sendfrm.n_initiation_type_3.value == 1) {
                initiationType1Str = "box_initiation_type_1.visible=false, line_initiation_type_1.visible=true, "
                    + "box_initiation_type_2.visible=false, line_initiation_type_2.visible=true, "
                    + "box_initiation_type_3.visible=true, line_initiation_type_3.visible=false, "
                    + "box_initiation_type_4.visible=false, line_initiation_type_4.visible=true, "
                    + "box_initiation_type_5.visible=false, line_initiation_type_5.visible=true, "
            } else if (sendfrm.n_initiation_type_4.value == 1) {
                initiationType1Str = "box_initiation_type_1.visible=false, line_initiation_type_1.visible=true, "
                    + "box_initiation_type_2.visible=false, line_initiation_type_2.visible=true, "
                    + "box_initiation_type_3.visible=false, line_initiation_type_3.visible=true, "
                    + "box_initiation_type_4.visible=true, line_initiation_type_4.visible=false, "
                    + "box_initiation_type_5.visible=false, line_initiation_type_5.visible=true, "
            } else if (sendfrm.n_initiation_type_5.value == 1) {
                initiationType1Str = "box_initiation_type_1.visible=false, line_initiation_type_1.visible=true, "
                    + "box_initiation_type_2.visible=false, line_initiation_type_2.visible=true, "
                    + "box_initiation_type_3.visible=false, line_initiation_type_3.visible=true, "
                    + "box_initiation_type_4.visible=false, line_initiation_type_4.visible=true, "
                    + "box_initiation_type_5.visible=true, line_initiation_type_5.visible=false, "
            } else {
                initiationType1Str = "box_initiation_type_1.visible=false, line_initiation_type_1.visible=false, "
                    + "box_initiation_type_2.visible=false, line_initiation_type_2.visible=false, "
                    + "box_initiation_type_3.visible=false, line_initiation_type_3.visible=false, "
                    + "box_initiation_type_4.visible=false, line_initiation_type_4.visible=false, "
                    + "box_initiation_type_5.visible=false, line_initiation_type_5.visible=false, "
            }

            // 発議事項（中央）
            var initiationType2Str = "";
            if (sendfrm.n_initiation_type_2_1.value == 1) {
                initiationType2Str = "box_initiation_type_1_2.visible=true, line_initiation_type_1_2.visible=false, "
                    + "box_initiation_type_2_2.visible=false, line_initiation_type_2_2.visible=true, "
                    + "box_initiation_type_3_2.visible=false, line_initiation_type_3_2.visible=true, "
                    + "box_initiation_type_4_2.visible=false, line_initiation_type_4_2.visible=true, "
                    + "box_initiation_type_5_2.visible=false, line_initiation_type_5_2.visible=true, "
            } else if (sendfrm.n_initiation_type_2_2.value == 1) {
                initiationType2Str = "box_initiation_type_1_2.visible=false, line_initiation_type_1_2.visible=true, "
                    + "box_initiation_type_2_2.visible=true, line_initiation_type_2_2.visible=false, "
                    + "box_initiation_type_3_2.visible=false, line_initiation_type_3_2.visible=true, "
                    + "box_initiation_type_4_2.visible=false, line_initiation_type_4_2.visible=true, "
                    + "box_initiation_type_5_2.visible=false, line_initiation_type_5_2.visible=true, "
            } else if (sendfrm.n_initiation_type_2_3.value == 1) {
                initiationType2Str = "box_initiation_type_1_2.visible=false, line_initiation_type_1_2.visible=true, "
                    + "box_initiation_type_2_2.visible=false, line_initiation_type_2_2.visible=true, "
                    + "box_initiation_type_3_2.visible=true, line_initiation_type_3_2.visible=false, "
                    + "box_initiation_type_4_2.visible=false, line_initiation_type_4_2.visible=true, "
                    + "box_initiation_type_5_2.visible=false, line_initiation_type_5_2.visible=true, "
            } else if (sendfrm.n_initiation_type_2_4.value == 1) {
                initiationType2Str = "box_initiation_type_1_2.visible=false, line_initiation_type_1_2.visible=true, "
                    + "box_initiation_type_2_2.visible=false, line_initiation_type_2_2.visible=true, "
                    + "box_initiation_type_3_2.visible=false, line_initiation_type_3_2.visible=true, "
                    + "box_initiation_type_4_2.visible=true, line_initiation_type_4_2.visible=false, "
                    + "box_initiation_type_5_2.visible=false, line_initiation_type_5_2.visible=true, "
            } else if (sendfrm.n_initiation_type_2_5.value == 1) {
                initiationType2Str = "box_initiation_type_1_2.visible=false, line_initiation_type_1_2.visible=true, "
                    + "box_initiation_type_2_2.visible=false, line_initiation_type_2_2.visible=true, "
                    + "box_initiation_type_3_2.visible=false, line_initiation_type_3_2.visible=true, "
                    + "box_initiation_type_4_2.visible=false, line_initiation_type_4_2.visible=true, "
                    + "box_initiation_type_5_2.visible=true, line_initiation_type_5_2.visible=false, "
            } else {
                initiationType2Str = "box_initiation_type_1_2.visible=false, line_initiation_type_1_2.visible=false, "
                    + "box_initiation_type_2_2.visible=false, line_initiation_type_2_2.visible=false, "
                    + "box_initiation_type_3_2.visible=false, line_initiation_type_3_2.visible=false, "
                    + "box_initiation_type_4_2.visible=false, line_initiation_type_4_2.visible=false, "
                    + "box_initiation_type_5_2.visible=false, line_initiation_type_5_2.visible=false, "
            }


            // 発議事項（上部）
            var initiationTypeResponseStr = "";
            if (sendfrm.n_response_type_1.value == 1) {
                initiationTypeResponseStr = "box_response_type_type_1.visible=true, line_response_type_type_1.visible=false, "
                    + "box_response_type_type_2.visible=false, line_response_type_type_2.visible=true, "
                    + "box_response_type_type_3.visible=false, line_response_type_type_3.visible=true, "
                    + "box_response_type_type_4.visible=false, line_response_type_type_4.visible=true, "
            } else if (sendfrm.n_response_type_2.value == 1) {
                initiationTypeResponseStr = "box_response_type_type_1.visible=false, line_response_type_type_1.visible=true, "
                    + "box_response_type_type_2.visible=true, line_response_type_type_2.visible=false, "
                    + "box_response_type_type_3.visible=false, line_response_type_type_3.visible=true, "
                    + "box_response_type_type_4.visible=false, line_response_type_type_4.visible=true, "
            } else if (sendfrm.n_response_type_3.value == 1) {
                initiationTypeResponseStr = "box_response_type_type_1.visible=false, line_response_type_type_1.visible=true, "
                    + "box_response_type_type_2.visible=false, line_response_type_type_2.visible=true, "
                    + "box_response_type_type_3.visible=true, line_response_type_type_3.visible=false, "
                    + "box_response_type_type_4.visible=false, line_response_type_type_4.visible=true, "
            } else if (sendfrm.n_response_type_4.value == 1) {
                initiationTypeResponseStr = "box_response_type_type_1.visible=false, line_response_type_type_1.visible=true, "
                    + "box_response_type_type_2.visible=false, line_response_type_type_2.visible=true, "
                    + "box_response_type_type_3.visible=false, line_response_type_type_3.visible=true, "
                    + "box_response_type_type_4.visible=true, line_response_type_type_4.visible=false, "
            } else {
                initiationTypeResponseStr = "box_response_type_type_1.visible=false, line_response_type_type_1.visible=false, "
                    + "box_response_type_type_2.visible=false, line_response_type_type_2.visible=false, "
                    + "box_response_type_type_3.visible=false, line_response_type_type_3.visible=false, "
                    + "box_response_type_type_4.visible=false, line_response_type_type_4.visible=false, "
            }

            if (setAttrObj != "") {
                if (setAttrObj.value != "") setAttrObj.value += ",";
                setAttrObj.value += initiationType1Str;
                setAttrObj.value += initiationType2Str;
                setAttrObj.value += initiationTypeResponseStr;
            }

            return true;
        }

        function clearByTempSave(fromfrm) {
            clearDefaultSignByTempSave();
        }

        function backFormTempSaveError(fromfrm) {
            backDefaultSignByTempSave();
        }

        var fieldArr = new Array();
        fieldArr[0] = "<?= (PN_REPMINUTE_PROP_DATE) ?>_2";
        fieldArr[1] = "<?= (PN_REPMINUTE_PROP_DATE) ?>_3";
        fieldArr[2] = "x_1";

        function go_next_fieldex(nowID, event) {
            for (i = 0; i < fieldArr.length; i++) {
                if (nowID == fieldArr[i]) {
                    go_next_field(fieldArr[i], event);
                    break;
                }
            }
        }

        /**
         * ラジオチェックボックスのクリック処理
         * @author XM.Zhong 2012.06.12
         * @param string clsName    同じグループ名
         * @param mixed value 選択値
         */
        function onRadioCheckboxClicked(el, clsName) {
            var class_name = el.className;
            if (class_name && class_name.indexOf('initiation_type') > -1) {
                $('input[name^=n_initiation_type]').not('.' + class_name).prop('checked', false);
                $('.' + class_name).prop('checked', el.checked);
            }
            var p = new RegExp("(^|\\s)" + clsName + "(\\s|$)");
            var chks = document.getElementsByTagName("input");
            for (var i = 0; i < chks.length; i++) {
                if (el == chks[i] || !p.test(chks[i].className)) continue;
                chks[i].checked = false;
            }
        }

        /**
         * 指定クラスを含んだフォームエレメントの使用可能状態を設定します。
         * @author XM.Zhong 2012.06.12
         * @param string clsName
         * @param array tags
         * @param bool enabled
         */
        function enableFormElsByClass(clsName, tags, enabled) {
            var p = new RegExp("(^|\\s)" + clsName + "(\\s|$)");
            var replaceEditClsP = /(^|\s)(?:editable)|(?:disable)(\s|$)/g;
            var newEditClsName = enabled ? "$1editable$2" : "$1disable$2";
            for (var i = 0; i < tags.length; i++) {
                var els = document.getElementsByTagName(tags[i]);
                for (var j = 0; j < els.length; j++) {
                    if (!p.test(els[j].className)) continue;
                    els[j].disabled = !enabled;
                    if (!enabled && checkElemType(els[j], "checkbox")) els[j].checked = false;
                    if (checkElemType(els[j], "text")) {
                        els[j].className = els[j].className.replace(replaceEditClsP, newEditClsName);
                    }
                }
            }
        }

        var propTypeFlg = "<?php echo($inputData["N_PROPONENT_TYPE"]); ?>";

        /**
         * 発議者の選択
         * @author XM.Zhong 2012.06.12
         * @param HTMLElement el
         */
        function prop_radioCheckbox(el) {

            //チェックボックスの状態を更新
            onRadioCheckboxClicked(el, "g_prop_type");

            if ("<?php echo($allEditFlg); ?>" == "") return;
        }

        function viewInitRepType(vwFlg) {
            var fromfrm = document.FORM_CIRLIST;
            if (vwFlg) {
                displayById("label_i_t_5");
                displayById("initiation_type_5");
                displayById("virt_h");
                displayNoneById("virt_v");
            } else {
                fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[4].checked = false;
                displayNoneById("label_i_t_5");
                displayNoneById("initiation_type_5");
                displayNoneById("virt_h");
                displayById("virt_v");
            }
        }

    </script>
	<?php
	//JavaScript非出力指定時以外は出力する。
}

echo "			<table class=\"func-tbl\" summary=\"指示・承諾・協議・提出・報告書\" id=\"uchiawasenaiyo1\">\n";
echo "				<tr>\n";
echo "					<td colspan=\"8\" class=\"formno\">第１２号様式</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "					<td colspan=\"8\" class=\"formname\">";
//					指示・承諾・協議・提出・報告書
//発議事項の共通属性
$propItemComAttr = array(
	"name" => PN_REPMINUTE_INIT_TYPE,
	"class" => "g_prop_items",
);
// 入力不可の場合はここでhiddenを吐く。
$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_1",
	"value" => MINUTE_INITTYPE_INSTRUCT,
	'class' => 'initiation_type_1_common'
));
$hide = array();
$hide["name"] = PN_REPMINUTE_INIT_TYPE;
$hide["value"] = $inputData["N_INITIATION_TYPE"];
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_1\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_INSTRUCT], $js, $hide) . "指示   ・   </label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_2",
	"value" => MINUTE_INITTYPE_AGREE,
	'class' => 'initiation_type_2_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_AGREE], $js, array()) . "承諾   ・   </label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_3",
	"value" => MINUTE_INITTYPE_CONFER,
	'class' => 'initiation_type_3_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_3\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_CONFER], $js, array()) . "協議    ・   </label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_4",
	"value" => MINUTE_INITTYPE_PRESENT,
	'class' => 'initiation_type_4_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_4\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_PRESENT], $js, array()) . "提出   ・   </label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_5",
	"value" => MINUTE_INITTYPE_REPORT,
	'class' => 'initiation_type_5_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_5\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_REPORT], $js, array()) . "報告</label>";

echo "　事\n";
echo "					</td>\n";
echo "				</tr>\n";

// 可変押印出力
$signsInfo_pdf = $signsInfo;
echo "				<tr>\n";
echo "					<td class=\"embSignArea\" colspan=\"8\">\n";
echo "						<div class=\"signArea\">\n";
echoSignTable($signsInfo, $loginUser, $peExec, "", false, "", $canBeUnion);
echo "						</div>\n";
echo "					</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td class='td-x2-x7' colspan=\"3\" >\n";
echo "<div class='x2'>";
$attr = array();
$attr["id"] = "x_2";
$attr["name"] = "x_2";
$attr["value"] = $x_2;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo getInputTextItem($infoEA['x_2'], $attr, $js, true);
echo "</div>";
echo "						<div class=\"x7\">";
//宛先
$attr = array();
$attr["id"] = "x_7";
$attr["name"] = "x_7";
$attr["value"] = $x_7;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo getInputTextItem($infoEA['x_7'], $attr, $js, true);
$textAtesaki = ($x_7 != "" || $infoEA["x_7"]) ? "様" : "";
echo "<span id=\"text_atesaki\">" . $textAtesaki . "</span>\n";
echo "</div>";

echo "					</td>\n";

echo "					<td class=\"td-propose-date-title input-title\"><span class='align-title'>発 年<br>月<br>議 日</span></td>\n";
echo "					<td class=\"td-propose-date r\" >\n";
// 発議年月日
echo "						";
$dateOutFlg = ($infoEA[BCODE_PROPOSE_DATE]
	|| (!$infoEA[BCODE_PROPOSE_DATE] && ($hatsugidate['wyear'] != "")
		&& ($hatsugidate['mon'] != "") && ($hatsugidate['mday'] != "")));
if ($dateOutFlg) echo $hatsugidate['nengo'];                            // 元号
$attr = array();
$attr["id"] = PN_REPMINUTE_PROP_DATE . "_1";
$attr["name"] = PN_REPMINUTE_PROP_DATE . "_1";
$attr["value"] = $hatsugidate['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('" . PN_REPMINUTE_PROP_DATE . "_2', event);\"";
echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);    // 年
if ($dateOutFlg) echo "年";
$attr = array();
$attr["id"] = PN_REPMINUTE_PROP_DATE . "_2";
$attr["name"] = PN_REPMINUTE_PROP_DATE . "_2";
$attr["value"] = $hatsugidate['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('" . PN_REPMINUTE_PROP_DATE . "_3', event);\"";
echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);    // 月
if ($dateOutFlg) echo "月";
$attr = array();
$attr["id"] = PN_REPMINUTE_PROP_DATE . "_3";
$attr["name"] = PN_REPMINUTE_PROP_DATE . "_3";
$attr["value"] = $hatsugidate['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);    // 日
if ($dateOutFlg) echo "日\n";
else    echo("&nbsp;\n");
echo "					</td>\n";

echo "					<td class=\"td-x5-x1-title input-title\" >発<br>議<br>者</td>\n";
echo "					<td class='td-x5-x10'  colspan='2'>\n";
$attr = array();
$attr["id"] = "x_5";
$attr["name"] = "x_5";
$attr["value"] = $x_5;
$attr["size"] = "40";
$attr["maxlength"] = "40";
$attr["class"] = "textfield";
$js = "";
echo "<div class='x5'>";
echo getInputTextItem($infoEA["x_5"], $attr, $js, true);
echo "</div>";
// 発議者氏名
$attr = array();
$attr["id"] = "x_10";
$attr["name"] = "x_10";
$attr["value"] = $x_10;
$attr["size"] = "40";
$attr["maxlength"] = "40";
$attr["class"] = "textfield";
$js = "";
echo "<div class='x10'>";
echo getInputTextItem($infoEA["x_10"], $attr, $js, true);
echo "</div>";
echo "</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td class=\"td-x8-title input-title\" >契約番号</td>\n";
echo "					<td  class='td-x8'  colspan='2'>";
// 契約番号
$attr = array();
$attr["id"] = "x_8";
$attr["name"] = "x_8";
$attr["value"] = $x_8;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x8'>";
if ($comKind == CK_COM_MAKE) echo "<span class='text-addtional'>第</span>";
echo getInputTextItem($infoEA["x_8"], $attr, $js, true);
if ($comKind == CK_COM_MAKE) echo "<span class='text-addtional'>号</span>";
echo "</span>";
echo "</td>";
echo "					<td class=\"td-x1-title input-title\" >工<br>事<br>名</td>\n";
echo "					<td  class='td-x1' >";
//工事名
$attr = array();
$attr["name"] = "x_1";
$attr["id"] = "x_1";
$attr["rows"] = "5";
$attr["cols"] = "32";
$js = "";

echo "<div class='x1'>";
echo getTextAreaItem($infoEA["x_1"], $attr, $x_1, $js, true);
echo "</div>";
echo "</td>";
echo "					<td class=\"td-x1-title input-title\" >受<br>注<br>者</td>\n";
echo "					<td  class='td-x3'  colspan='2'>";
//受注者
$attr = array();
$attr["name"] = "x_3";
$attr["id"] = "x_3";
$attr["rows"] = "3";
$attr["cols"] = "20";
$js = "";
echo "<div class='x3'>";
echo getTextAreaItem($infoEA["x_3"], $attr, $x_3, $js, true);
echo "</div>";
echo "</td>";

echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td class=\"td-x6-title input-title\" >工事場所</td>\n";
echo "					<td class='td-x6' colspan=\"7\">";
//工事場所
$attr = array();
$attr["id"] = "x_6";
$attr["name"] = "x_6";
$attr["value"] = $x_6;
$attr["size"] = "80";
$attr["maxlength"] = "80";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x6'>";
echo getInputTextItem($infoEA["x_6"], $attr, $js, true);
echo "</span>";

echo "</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td class=\"input-title\" rowspan=\"2\" >工　　期</td>\n";
echo "					<td id=\"text_sKoki\">自</td>\n";
echo "					<td id=\"date_sKoki\" colspan=\"6\" class=\"td-x11 r\" >\n";
echo "						";
// 工期-開始日
echo "<span class='x11'>";
$dateOutFlg = ($infoEA["x_11"]
	|| (!$infoEA["x_11"] && ($x_11['wyear'] != "")
		&& ($x_11['mon'] != "") && ($x_11['mday'] != "")));
if ($dateOutFlg) echo $x_11['nengo'];            // 元号
$attr = array();
$attr["id"] = "x_11_1";
$attr["name"] = "x_11_1";
$attr["value"] = $x_11['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_11_2', event);\"";
echo getInputTextItem($infoEA["x_11"], $attr, $js, true);    // 年
if ($dateOutFlg) echo "年";
$attr = array();
$attr["id"] = "x_11_2";
$attr["name"] = "x_11_2";
$attr["value"] = $x_11['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_11_3', event);\"";
echo getInputTextItem($infoEA["x_11"], $attr, $js, true);    // 月
if ($dateOutFlg) echo "月";
$attr = array();
$attr["id"] = "x_11_3";
$attr["name"] = "x_11_3";
$attr["value"] = $x_11['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
echo getInputTextItem($infoEA["x_11"], $attr, $js, true);    // 日
if ($dateOutFlg) echo "日\n";
else    echo("&nbsp;\n");
echo "</span>";

// 年月日入力
echo "<span class='x13'>（工事着手日　：　";
$dateOutFlg = ($infoEA["x_13"]
	|| (!$infoEA["x_13"] && ($x_13['wyear'] != "")
		&& ($x_13['mon'] != "") && ($x_13['mday'] != "")));
if ($dateOutFlg) echo $x_13['nengo'];            // 元号
$attr = array();
$attr["id"] = "x_13_1";
$attr["name"] = "x_13_1";
$attr["value"] = $x_13['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_13_2', event);\"";
echo getInputTextItem($infoEA["x_13"], $attr, $js, true);    // 年
if ($dateOutFlg) echo "年";
$attr = array();
$attr["id"] = "x_13_2";
$attr["name"] = "x_13_2";
$attr["value"] = $x_13['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_13_3', event);\"";
echo getInputTextItem($infoEA["x_13"], $attr, $js, true);    // 月
if ($dateOutFlg) echo "月";
$attr = array();
$attr["id"] = "x_13_3";
$attr["name"] = "x_13_3";
$attr["value"] = $x_13['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
echo getInputTextItem($infoEA["x_13"], $attr, $js, true);    // 日
if ($dateOutFlg) echo "日\n";
else    echo("&nbsp;\n");
echo "　　　　）</span>";


echo "					</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td id=\"text_eKoki\">至</td>\n";
echo "					<td id=\"date_eKoki\" colspan=\"6\" class=\"td-x12 r\" >\n";
echo "						";
//工期-終了日
$dateOutFlg = ($infoEA["x_12"]
	|| (!$infoEA["x_12"] && ($x_12['wyear'] != "")
		&& ($x_12['mon'] != "") && ($x_12['mday'] != "")));
if ($dateOutFlg) echo $x_12['nengo'];                // 元号
$attr = array();
$attr["id"] = "x_12_1";
$attr["name"] = "x_12_1";
$attr["value"] = $x_12['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_12_2', event);\"";

echo getInputTextItem($infoEA["x_12"], $attr, $js, true);    // 年
if ($dateOutFlg) echo "年";
$attr = array();
$attr["id"] = "x_12_2";
$attr["name"] = "x_12_2";
$attr["value"] = $x_12['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_12_3', event);\"";
echo getInputTextItem($infoEA["x_12"], $attr, $js, true);    // 月
if ($dateOutFlg) echo "月";
$attr = array();
$attr["id"] = "x_12_3";
$attr["name"] = "x_12_3";
$attr["value"] = $x_12['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
echo getInputTextItem($infoEA["x_12"], $attr, $js, true);    // 日
if ($dateOutFlg) echo "日\n";
else    echo("&nbsp;\n");
echo "					</td>\n";
echo "				</tr>\n";


echo "				<tr>\n";
echo "					<td class=\"td-x14-title input-title\"  colspan='2'>監理事務所名</td>\n";
echo "					<td class='td-x14' colspan=\"7\">";
//工事場所
$attr = array();
$attr["id"] = "x_14";
$attr["name"] = "x_14";
$attr["value"] = $x_14;
$attr["size"] = "76";
$attr["maxlength"] = "76";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x14'>";
echo getInputTextItem($infoEA["x_14"], $attr, $js, true);
echo "</span>";

echo "</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo '<td class="td-x15-title input-title"  colspan="2">建  築</td>';
echo '<td class="td-x15-title input-title" >立  会  人</td>';
echo "					<td  class='td-x15'  colspan='4'>";
// 建築立会人
$attr = array();
$attr["id"] = "x_15";
$attr["name"] = "x_15";
$attr["value"] = $x_15;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x15'>";
echo getInputTextItem($infoEA["x_15"], $attr, $js, true);
echo "</span>";
echo "</td>";

echo '<td class="td-x15-empty"  colspan="2"></td>';

echo "					</tr>";

echo "				<tr>\n";
echo '<td class="td-x16-title input-title"  colspan="2">建  築</td>';
echo '<td class="td-x16-title input-title" >立  会  人</td>';
echo "					<td  class='td-x16'  colspan='4'>";
// 建築立会人
$attr = array();
$attr["id"] = "x_16";
$attr["name"] = "x_16";
$attr["value"] = $x_16;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x16'>";
echo getInputTextItem($infoEA["x_16"], $attr, $js, true);
echo "</span>";
echo "</td>";

echo '<td class="td-x16-empty"  colspan="2"></td>';

echo "					</tr>";

echo "				<tr>\n";
echo '<td class="td-x17-title input-title"  colspan="2">建  築</td>';
echo '<td class="td-x17-title input-title" >立  会  人</td>';
echo "					<td  class='td-x17'  colspan='4'>";
// 建築立会人
$attr = array();
$attr["id"] = "x_17";
$attr["name"] = "x_17";
$attr["value"] = $x_17;
$attr["size"] = "20";
$attr["maxlength"] = "20";
$attr["class"] = "textfield";
$js = "";
echo "<span class='x17'>";
echo getInputTextItem($infoEA["x_17"], $attr, $js, true);
echo "</span>";
echo "</td>";

echo '<td class="td-x17-empty"  colspan="2"></td>';

echo "					</tr>";


echo "				<tr>\n";
echo "					<td id=\"row_propType\" colspan=\"8\" class=\"r\">\n";
//発議事項の共通属性
$n_initiation_type_2 = PN_REPMINUTE_INIT_TYPE . '_2';
$propItemComAttr = array(
	"name" => $n_initiation_type_2,
	"class" => "g_prop_items",
);
// 入力不可の場合はここでhiddenを吐く。
$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_1_2",
	"value" => MINUTE_INITTYPE_INSTRUCT,
	'class' => 'initiation_type_1_common'
));
$hide = array();
$hide["name"] = $n_initiation_type_2;
$hide["value"] = $inputData["N_INITIATION_TYPE"];
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_1_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_INSTRUCT], $js, $hide) . "指示</label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_2_2",
	"value" => MINUTE_INITTYPE_AGREE,
	'class' => 'initiation_type_2_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_2_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_AGREE], $js, array()) . "承諾</label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_3_2",
	"value" => MINUTE_INITTYPE_CONFER,
	'class' => 'initiation_type_3_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_3_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_CONFER], $js, array()) . "協議</label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_4_2",
	"value" => MINUTE_INITTYPE_PRESENT,
	'class' => 'initiation_type_4_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_4_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_PRESENT], $js, array()) . "提出</label>\n";

$attr = array_merge($propItemComAttr, array(
	"id" => "initiation_type_5_2",
	"value" => MINUTE_INITTYPE_REPORT,
	'class' => 'initiation_type_5_common'
));
$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
echo "						<label for=\"initiation_type_5_2\">"
	. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
		, $n_initiation_type[MINUTE_INITTYPE_REPORT], $js, array()) . "報告</label>";

echo "　事項\n";
echo "					</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td colspan=\"8\" class=\"td-minutes_detail minutes_detail\">\n";
$attr = array();
$attr["name"] = PN_REPMINUTE_DETAIL;
$attr["id"] = "minutes_detail";
$attr["rows"] = "13";
$attr["cols"] = "86";
echo "						";
if (!$infoEA[BCODE_MINUTES_DETAIL]) echo("<div class=\"m_detail\">");
echo getTextAreaItem($infoEA[BCODE_MINUTES_DETAIL], $attr, $inputData["C_MINUTES_DETAIL"], "", true);
if ($infoEA[BCODE_MINUTES_DETAIL]) echo("<br>");
else    echo("</div><br>");
echo("\n");
echo "					</td>\n";
echo "				</tr>\n";

echo "				<tr>\n";
echo "					<td class=\"td-response-title input-title\" colspan=\"8\" >処  理  ・  回  答</td>\n";
echo "				</tr>\n";

$chkDispCls = " class=\"g_response1\"";
$txtDispCls = " class=\"g_response1 editable textfield\"";
$dTxtDispCls = " class=\"g_response1 editable textfield input-date\"";
$taDispCls = " class=\"g_response1 otherta\"";
$resCls = "";
echo "				<tr id=\"respClient\" class=\"" . $resCls . "\">\n";
echo "					<td colspan=\"8\" class=\"r\">\n";
echo "						<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" summary=\"処理回答\" class=\"noneborder\">\n";
echo "							<tr class=\"smallrow\">\n";
echo "								<td class=\"nochk\" >上記事項については、</td>\n";
$resComAttr = array(
	"name" => "n_response_type",
);
$attr = array_merge(array(
	"id" => "n_response_type_1",
	"value" => MINUTE_RESPONSE_CONSENT,
), $resComAttr);
$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
$hide = array();
$hide["name"] = "n_response_type";
if ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA) {
	$hide["value"] = $inputData["N_RESPONSE_TYPE"];
} else {
	$hide["value"] = $inputData["N_SYORI_TYPE"];
}
echo "								<td class=\"chkfirst\"><label for=\"n_response_type_1\">"
	. getInputCheckboxItem($infoEA[BCODE_RESPONSE_TYPE], $attr, $n_response_type[MINUTE_RESPONSE_CONSENT]
		, $js, $hide) . "了解</label></td>\n";

$attr = array_merge(array(
	"id" => "n_response_type_2",
	"value" => MINUTE_RESPONSE_AGREE,
), $resComAttr);
$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
echo "								<td><label for=\"n_response_type_2\">"
	. getInputCheckboxItem($infoEA[BCODE_RESPONSE_TYPE], $attr, $n_response_type[MINUTE_RESPONSE_AGREE], $js, array())
	. "承諾</label></td>\n";

$attr = array_merge(array(
	"id" => "n_response_type_3",
	"value" => MINUTE_RESPONSE_INSTRUCT,
), $resComAttr);
$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
echo "								<td><label for=\"n_response_type_3\">"
	. getInputCheckboxItem($infoEA[BCODE_RESPONSE_TYPE], $attr, $n_response_type[MINUTE_RESPONSE_INSTRUCT], $js, array())
	. "後日指示</label></td>\n";

$attr = array_merge(array(
	"id" => "n_response_type_4",
	"value" => MINUTE_RESPONSE_ACCEPT,
), $resComAttr);
$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
echo "								<td><label for=\"n_response_type_4\">"
	. getInputCheckboxItem($infoEA[BCODE_RESPONSE_TYPE], $attr, $n_response_type[MINUTE_RESPONSE_ACCEPT], $js, array())
	. "受理</label></td>\n";

echo "								<td class=\"nochk\" >する。協議のとおり施工すること。</td>\n";
echo "							</tr>\n";
echo "							<tr class=\"largerow\">\n";
echo "								<td colspan=\"8\" class=\"td-c-response-type-other-detail kakko\">";
$attr = array();
$attr["id"] = "c_response_type_other_detail";
$attr["name"] = "c_response_type_other_detail";
$attr["rows"] = "13";
$attr["cols"] = "86";
$js = $taDispCls;
if (!$infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL]) echo("<div class=\"resp_detail\">");
echo getTextAreaItem($infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL], $attr, $inputData['C_RESPONSE_TYPE_OTHER_DETAIL'], $js, true);
if (!$infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL]) echo("</div><br>");
echo "</td>\n";
echo "							</tr>\n";
echo "							<tr class=\"smallrow\">\n";

// 指示日
$x_18_input = "<span class='x18'>";
$dateOutFlg = ($infoEA["x_18"]
	|| (!$infoEA["x_18"] && ($x_18['wyear'] != "")
		&& ($x_18['mon'] != "") && ($x_18['mday'] != "")));
if ($dateOutFlg) $x_18_input .= $x_18['nengo'];            // 元号
$attr = array();
$attr["id"] = "x_18_1";
$attr["name"] = "x_18_1";
$attr["value"] = $x_18['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_18_2', event);\"";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);    // 年
if ($dateOutFlg) $x_18_input .= "年";
$attr = array();
$attr["id"] = "x_18_2";
$attr["name"] = "x_18_2";
$attr["value"] = $x_18['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_18_3', event);\"";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);    // 月
if ($dateOutFlg) $x_18_input .= "月";
$attr = array();
$attr["id"] = "x_18_3";
$attr["name"] = "x_18_3";
$attr["value"] = $x_18['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);    // 日
if ($dateOutFlg) $x_18_input .= "日\n";
else    $x_18_input .= ("&nbsp;\n");
$x_18_input .= "</span>";


echo "								<td colspan=\"8\" align=\"left\" >※協議事項に対して検討時間のかかる場合は、$x_18_input までに指示」するものとする。</td>\n";
echo "							</tr>\n";
echo "							<tr class=\"smallrow\">\n";
echo "								<td colspan=\"8\" align=\"right\">\n";
echo "									";
$dateOutFlg = ($infoEA[BCODE_RESPONSE_DATE]
	|| (!$infoEA[BCODE_RESPONSE_DATE] && ($d_response_date['wyear'] != "")
		&& ($d_response_date['mon'] != "") && ($d_response_date['mday'] != "")));
if ($dateOutFlg) echo $d_response_date['nengo'];            // 元号
$attr = array();
$attr["id"] = PN_REPMINUTE_RESP_DATE . "_1";
$attr["name"] = PN_REPMINUTE_RESP_DATE . "_1";
$attr["value"] = $d_response_date['wyear'];
$attr["maxlength"] = "2";
$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_2', event);\"";
echo getInputTextItem($infoEA[BCODE_RESPONSE_DATE], $attr, $js, true);    // 年
if ($dateOutFlg) echo "年";
$attr = array();
$attr["id"] = PN_REPMINUTE_RESP_DATE . "_2";
$attr["name"] = PN_REPMINUTE_RESP_DATE . "_2";
$attr["value"] = $d_response_date['mon'];
$attr["maxlength"] = "2";
$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_3', event);\"";
echo getInputTextItem($infoEA[BCODE_RESPONSE_DATE], $attr, $js, true);    // 月
if ($dateOutFlg) echo "月";
$attr = array();
$attr["id"] = PN_REPMINUTE_RESP_DATE . "_3";
$attr["name"] = PN_REPMINUTE_RESP_DATE . "_3";
$attr["value"] = $d_response_date['mday'];
$attr["maxlength"] = "2";
$js = $dTxtDispCls;
echo getInputTextItem($infoEA[BCODE_RESPONSE_DATE], $attr, $js, true);    // 日
if ($dateOutFlg) echo "日";
if (!$dateOutFlg) {
	echo "<img src=\"" . $sessionInfo->image_default_dir . "spacer.gif" . "\""
		. " alt=\"\" width=\"190\" height=\"1\">";
} else if (!$infoEA[BCODE_RESPONSE_DATE]) {
	echo "<img src=\"" . $sessionInfo->image_default_dir . "spacer.gif" . "\""
		. " alt=\"\" width=\"80\" height=\"1\">";
}
echo "\n";
echo "								</td>\n";
echo "							</tr>\n";
echo "						</table>\n";
echo "					</td>\n";
echo "				</tr>\n";

echo "			</table>\n";

echo "			<table id=\"comments\">\n";
echo "				<tr>\n";
echo "					<td><span class='text-fixed'> （注）</span></td>\n";
echo "					<td>２部作成し、捺印後発注者と受注者が各１部保管するものとする。</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "					<td>&nbsp;</td>\n";
echo "					<td>不要な文字は、<span class=\"lineThrough\">　　　</span>で消すこと。</td>\n";
echo "				</tr>\n";
echo "			</table>\n";

echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_TYPE . "\" value=\"" . ROLE_UKEOISHA . "\">\n";
echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_DATE . "\" value=\"" . htmlspecialchars($hatsugidate['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_CAT . "\" value=\"" . ROLE_HACHUSHA . "\">\n";
echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "\" value=\"" . htmlspecialchars($d_response_date['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"can_be_union\" value=\"" . $canBeUnion . "\">";
echo "			<input type=\"hidden\" name=\"x_1" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_1_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_2" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_2_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_3" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_3_id . "\">\n";
//	echo"			<input type=\"hidden\" name=\"x_4" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_4_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_5" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_5_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_6" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_6_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_7" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_7_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_8" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_8_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_10" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_10_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_11" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_11_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_11\" value=\"" . $x_11['value'] . "\">\n";
echo "			<input type=\"hidden\" name=\"x_12" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_12_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_12\" value=\"" . htmlspecialchars($x_12['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"x_13" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_13_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_13\" value=\"" . htmlspecialchars($x_13['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"x_14" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_14_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_15" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_15_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_16" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_16_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_17" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_17_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_18" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_18_id . "\">\n";
echo "			<input type=\"hidden\" name=\"x_18\" value=\"" . htmlspecialchars($x_18['value'], ENT_QUOTES) . "\">\n";

echo "			<div id=\"routeIDs\">\n";
echo "			</div>\n";

//JavaScript非出力指定時以外は関数定義する。
if (!$jsNoneFlg) {
	function outputPDFForm()
	{
		echo "			<input type=\"hidden\" name=\"" . PN_PDF_SETATTR . "\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_DATE . "\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_1\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_4\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_5\" value=\"\">\n";
		
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2_1\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2_4\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_initiation_type_2_5\" value=\"\">\n";
		
		echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_DETAIL . "\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_response_type_1\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_response_type_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_response_type_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"n_response_type_4\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"c_response_type_other_detail\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"c_response_type_other_detail_1\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"c_response_type_other_detail_2\" value=\"\">\n";
		
		echo "			<input type=\"hidden\" name=\"x_1\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_1_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_1_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_1_4\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_1_5\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_3_2\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_3_3\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_5\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_6\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_7\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_8\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_10\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_11\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_12\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_13\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_14\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_15\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_16\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_17\" value=\"\">\n";
		echo "			<input type=\"hidden\" name=\"x_18\" value=\"\">\n";
		
		global $signsInfo_pdf;
		echoSignPDFParam($signsInfo_pdf);
	}
}
?>
