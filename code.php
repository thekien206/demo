<?php

	// 岐阜県「第１２号様式　指示・承諾・協議・提出・報告書」

	require_once("com_str.phl");
	require_once("circular/crcl_rep_vwfunc2.phl");
	global $signsInfo_pdf;

	$hatsugisyaChk = array(ROLE_HACHUSHA => false, ROLE_UKEOISHA => false);
	$hatsugijikouChk = array(MINUTE_INITTYPE_INSTRUCT => false, MINUTE_INITTYPE_AGREE => false
		, MINUTE_INITTYPE_CONFER => false, MINUTE_INITTYPE_PRESENT => false
		, MINUTE_INITTYPE_REPORT => false);

	$hacchusyasyoriChk = array(MINUTE_RESPONSE_CONSENT => false, MINUTE_RESPONSE_AGREE => false
		, MINUTE_RESPONSE_INSTRUCT => false, MINUTE_RESPONSE_ACCEPT => false);
	$hacchusyasyorisonota = "";
	$ukeoisyoriChk = array(MINUTE_RESPONSE_CONSENT => false, MINUTE_RESPONSE_AGREE => false
		, MINUTE_RESPONSE_INSTRUCT => false, MINUTE_RESPONSE_ACCEPT => false);
	$ukeoisyorisonota = "";

	// 起案画面表示時のデータ取得
	if (($act_code == AC_SHOW_START_CIRCULAR) && !$rcExec && !$befMkFlg) {
		$inputData["N_PROPONENT_TYPE"] = $loginRole["N_PROPONENT_TYPE"];
		if (($inputData["N_PROPONENT_TYPE"] != ROLE_UKEOISHA)
				 && ($inputData["N_PROPONENT_TYPE"] != ROLE_HACHUSHA)) {
			$inputData["N_PROPONENT_TYPE"] = ROLE_UKEOISHA;
		}
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
				if (!is_array($docs))	$docs = array();
				if ($firstFlg) {
					if ($firstUKID == "") {
						$firstUKID = $messInfo["CKID"] . $messInfo["CUID"];
						$insDT = $messInfo["D_UPDATE_DT"];
					}
					if ($firstUKID == $messInfo["CKID"] . $messInfo["CUID"])	$firstFlg = false;
				}
				foreach ($docs as $doc) {
					if ($docTemp != "")	$docTemp .= ", ";
					$docTemp .= $doc["C_FILE_NAME"];
				}
				switch ($messInfo["C_KIND"]) {
					// 協議
					case 0:
					// 完了
					case 3:
						if ($minutes_detail != "")	$minutes_detail .= "\r\n";
						$minutes_detail .= $messInfo["C_MESSAGE"];
					break;
					// 回答
					case 1:
						if ($kaitoRes != "")	$kaitoRes .= "\r\n";
						$kaitoRes .= $messInfo["C_MESSAGE"];
						$kaitoDT = $messInfo["D_UPDATE_DT"];
					break;
					// 回答期限...おそらく不要だが念のため
					case 2:
						if ($kaitoRes != "")	$kaitoRes .= "\r\n";
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
	if ($inputData["N_PROPONENT_TYPE"] == ROLE_HACHUSHA) {
		$hatsugisyaChk[$inputData["N_PROPONENT_TYPE"]] = true;
		if ($inputData["N_SYORI_TYPE"] != "") {
			$hacchusyasyoriChk[$inputData["N_SYORI_TYPE"]] = " checked";
		}
		$hacchusyasyorisonota = $inputData["C_SYORI_TYPE_OTHER_DETAIL"];
	} else if ($inputData["N_PROPONENT_TYPE"] == ROLE_UKEOISHA) {
		$hatsugisyaChk[$inputData["N_PROPONENT_TYPE"]] = true;
		if ($inputData["N_SYORI_TYPE"] != "") {
			$ukeoisyoriChk[$inputData["N_SYORI_TYPE"]] = " checked";
		}
		$ukeoisyorisonota = $inputData["C_SYORI_TYPE_OTHER_DETAIL"];
	}

	// 発議年月日
	$hatsugidate = CMainFuncCtrl::circularDateSet(
		$inputData["D_PROPOSE_DATE"],
		$infoEA[BCODE_PROPOSE_DATE],
		['name' => CMainFuncCtrl::setNengoString(PN_REPMINUTE_PROP_DATE)]
	);

	// 発議事項
	if ($inputData["N_INITIATION_TYPE"] != "") {
		$hatsugijikouChk[$inputData["N_INITIATION_TYPE"]] = " checked";
	}

	// 内容
	$minutes_detail = $inputData["C_MINUTES_DETAIL"];

	// 工事名
	$x_1_id = "";
	$x_1 = "";
		if (is_array($inputExData["x_1"]) && (count($inputExData["x_1"]) > 0)) {
			$x_1 = $inputExData["x_1"][0]["C_INPUT_CONTENT"];
			$x_1_id = $inputExData["x_1"][0]["N_REPORT_DETAIL_ID"];
		} else if ($comKind == CK_COM_MAKE) {
			// 打合簿,履行報告で最後に発議した帳票の入力値を取得
			$x_1 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "1");
			// 発議済み情報から取得できない場合は現場情報から取得
			if ($x_1 == "")	$x_1 = $genbaInfo["genba_name"];
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
			if ($x_2 == "")	$x_2 = $genbaInfo["genba_cd"];
		}

	// 受注者
	$x_3_id = "";
	$x_3 = "";
//	if (!$lumpKakuninFlg) {
		if (is_array($inputExData["x_3"]) && (count($inputExData["x_3"]) > 0)) {
			$x_3 = $inputExData["x_3"][0]["C_INPUT_CONTENT"];
			$x_3_id = $inputExData["x_3"][0]["N_REPORT_DETAIL_ID"];
		} else if ($comKind == CK_COM_MAKE) {
			// 打合簿,履行報告で最後に発議した帳票の入力値を取得
			$x_3 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "3");
			// 発議済み情報から取得できない場合は現場情報から取得
			if ($x_3 == "")	$x_3 = $genbaInfo["ukeoisha"];
			// 現場情報から取得できない、且つ受注者発議の場合はログインユーザーの所属会社名を取得
			if ($x_3 == "" && $inputData["N_PROPONENT_TYPE"] == ROLE_UKEOISHA)	$x_3 = $loginUser["C_HOJIN_NAME"];
		}
//	}
//	else {
//		// 一括発議の確認画面では、表示用に値取得 ※現場ごと
//		$x_3 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "3");
//		if ($x_3 == "")	$x_3 = $_POST["ukeoisha_gifu_" . $kaishaID . $genbaID];
//		if ($x_3 == "")	$x_3 = $genbaInfo["ukeoisha"];
//	}

	// 工事場所
//	$x_4_id = "";
//	$x_4 = "";
//	if (!$lumpKakuninFlg) {
//		if (is_array($inputExData["x_4"]) && (count($inputExData["x_4"] > 0))) {
//			$x_4 = $inputExData["x_4"][0]["C_INPUT_CONTENT"];
//			$x_4_id = $inputExData["x_4"][0]["N_REPORT_DETAIL_ID"];
//		} else if ($comKind == CK_COM_MAKE) {
//			// 打合簿,履行報告で最後に発議した帳票の入力値を取得
//			$x_4 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "4");
//			// 発議済み情報から取得できない場合は現場情報から取得
//			if ($x_4 == "")	$x_4 = $genbaInfo["genba_plc"];
//		}
//	} else {
//		// 一括発議の確認画面では、表示用に値取得 ※現場ごと
//		$x_4 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "4");
//		if ($x_4 == "")	$x_4 = $_POST["basyo_gifu_" . $kaishaID . $genbaID];
//		if ($x_4 == "")	$x_4 = $genbaInfo["genba_plc"];
//	}

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
	}
	
//	if (!$lumpKakuninFlg) {
//		if ((is_array($inputExData["x_5"]) && (count($inputExData["x_5"] > 0)))
//			 || (is_array($inputExData["x_6"]) && (count($inputExData["x_6"] > 0)))) {
//			$x_5 = $inputExData["x_5"][0]["C_INPUT_CONTENT"];
//			$x_5_id = $inputExData["x_5"][0]["N_REPORT_DETAIL_ID"];
//			$x_6 = $inputExData["x_6"][0]["C_INPUT_CONTENT"];
//			$x_6_id = $inputExData["x_6"][0]["N_REPORT_DETAIL_ID"];
//		} else if ($comKind == CK_COM_MAKE) {
//			// 最後に発議した帳票が打合簿,履行報告のどちらかを判定
//			if (getLatestReportMasterID($ora, $kaishaID, $genbaID, $regionID, $repType)) {
//				switch ($repType) {
//					case CRCL_MASID_GIFU_MINUTES:
//						// 打合簿で最後に発議した帳票の入力値を取得
//						$x_5 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "5");
//						$x_6 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "6");
//						break;
//					case CRCL_MASID_GIFU_REPORT:
//						// 履行報告で最後に発議した帳票の入力値を取得
//						getLatestIwateData($ora, $kaishaID, $genbaID, $regionID, $repType, $latestInputData);
//						if (is_array($latestInputData) && $latestInputData["D_START_DATE"] != "") {
//							$x_5 = $latestInputData["D_START_DATE"];
//						}
//						if (is_array($latestInputData) && $latestInputData["D_END_DATE"] != "") {
//							$x_6 = $latestInputData["D_END_DATE"];
//						}
//						break;
//				}
//			}
//			// 発議済み情報から取得できない場合は現場情報から取得
//			if ($x_5 == "")	$x_5 = $genbaInfo["gstart"];
//			if ($x_6 == "")	$x_6 = $genbaInfo["gend"];
//		}
//	} else {
//		// 一括発議の確認画面では、表示用に値取得 ※現場ごと
//		if (getLatestReportMasterID($ora, $kaishaID, $genbaID, $regionID, $repType)) {
//			switch ($repType) {
//				case CRCL_MASID_GIFU_MINUTES:
//					// 打合簿で最後に発議した帳票の入力値を取得
//					$x_5 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "5");
//					$x_6 = getLatestGifuReportInputData($ora, $kaishaID, $genbaID, "6");
//					break;
//				case CRCL_MASID_GIFU_REPORT:
//					// 履行報告で最後に発議した帳票の入力値を取得
//					getLatestIwateData($ora, $kaishaID, $genbaID, $regionID, $repType, $latestInputData);
//					if (is_array($latestInputData) && $latestInputData["D_START_DATE"] != "") {
//						$x_5 = $latestInputData["D_START_DATE"];
//					}
//					if (is_array($latestInputData) && $latestInputData["D_END_DATE"] != "") {
//						$x_6 = $latestInputData["D_END_DATE"];
//					}
//					break;
//			}
//		}
//		if ($x_5 == "")	$x_5 = $_POST["sKouki_gifu_" . $kaishaID . $genbaID];
//		if ($x_6 == "")	$x_6 = $_POST["eKouki_gifu_" . $kaishaID . $genbaID];
//		if ($x_5 == "")	$x_5 = $genbaInfo["gstart"];
//		if ($x_6 == "")	$x_6 = $genbaInfo["gend"];
//	}
    // 宛先
	$x_7 = "";
	$x_7_id = "";
	if (is_array($inputExData["x_7"]) && (count($inputExData["x_7"]) > 0)) {
		$x_7 = $inputExData["x_7"][0]["C_INPUT_CONTENT"];
		$x_7_id = $inputExData["x_7"][0]["N_REPORT_DETAIL_ID"];
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
}


//工期-開始日
$x_11 = "";
$x_11_id = "";
if (is_array($inputExData["x_11"]) && (count($inputExData["x_11"]) > 0)) {
	$x_11 = $inputExData["x_11"][0]["C_INPUT_CONTENT"];
	$x_11_id = $inputExData["x_11"][0]["N_REPORT_DETAIL_ID"];
}
$infoEA["x_11"] = TRUE; //TEST
$x_11_date = CMainFuncCtrl::circularDateSet(
	$x_11,
	$infoEA['x_11'],
	['name' => CMainFuncCtrl::setNengoString('x_11')]
);
//	$x_11Attr = [];
//	$x_11Attr['name'] = CMainFuncCtrl::setNengoString('x_11');
//	$x_11Attr['id'] = $x_11Attr['name'];
//	if ($lumpFlg && !$lumpKakuninFlg)	$x_11Attr["disabled"] = "disabled";
//    $infoEA["x_11"] = TRUE; //TEST
//	$x_11_date = CMainFuncCtrl::circularDateSet(
//		$x_11,
//		$infoEA["x_11"],
//		$x_11Attr
//	);

	//工期-終了日
$x_12 = '';
$x_12_id = '';
if (is_array($inputExData["x_12"]) && (count($inputExData["x_12"]) > 0)) {
	$x_12 = $inputExData["x_12"][0]["C_INPUT_CONTENT"];
	$x_12_id = $inputExData["x_12"][0]["N_REPORT_DETAIL_ID"];
}
$infoEA["x_12"] = TRUE; //TEST
$x_12_date = CMainFuncCtrl::circularDateSet(
	$x_12,
	$infoEA['x_12'],
	['name' => CMainFuncCtrl::setNengoString('x_12')]
);

//	$x_12Attr = [];
//	$x_12Attr['name'] = CMainFuncCtrl::setNengoString('x_12');
//	$x_12Attr['id'] = $x_12Attr['name'];
////	if ($lumpFlg && !$lumpKakuninFlg)	$x_12Attr["disabled"] = "disabled";
//
//	$x_12_date = CMainFuncCtrl::circularDateSet(
//		$x_12,
//		$infoEA["x_12"],
//		$x_12Attr
//	);
//
	

	//工事着手日
$x_13 = '';
$x_13_id = '';
if (is_array($inputExData["x_13"]) && (count($inputExData["x_13"]) > 0)) {
	$x_13 = $inputExData["x_13"][0]["C_INPUT_CONTENT"];
	$x_13_id = $inputExData["x_13"][0]["N_REPORT_DETAIL_ID"];
}
$infoEA["x_13"] = TRUE; //TEST
$x_13_date = CMainFuncCtrl::circularDateSet(
	$x_13,
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
$x_18 = '';
$x_18_id = '';
if (is_array($inputExData["x_18"]) && (count($inputExData["x_18"]) > 0)) {
	$x_18 = $inputExData["x_18"][0]["C_INPUT_CONTENT"];
	$x_18_id = $inputExData["x_18"][0]["N_REPORT_DETAIL_ID"];
}
$infoEA["x_18"] = TRUE; //TEST
$x_18_date = CMainFuncCtrl::circularDateSet(
	$x_18,
	$infoEA['x_18'],
	['name' => CMainFuncCtrl::setNengoString('x_18')]
);
	// 宛先
//	$x_7 = "";
//	$x_7_id = "";
	// 承認順序変更前は、以下を反映
	// ・受注者発議の場合…承認順序で最初の発注者役割ユーザー氏名
	// ・発注者発議の場合…承認順序で最初の受注者役割ユーザー氏名
//	if (is_array($defRoutes) && (count($defRoutes > 0))) {
//		$chkType = $defRoutes[0]["N_PROPONENT_TYPE"];	//発議者の役割区分
//		for ($rCnt = 1; $rCnt < count($defRoutes); $rCnt++) {
//			if ($chkType != $defRoutes[$rCnt]["N_PROPONENT_TYPE"]) {
//				$x_7_def = $defRoutes[$rCnt]["users"][0]["C_SHAIN_NAME"];
//				break;
//			}
//		}
//	}
//	if (!$lumpKakuninFlg) {
//		if (is_array($inputExData["x_7"]) && (count($inputExData["x_7"] > 0))) {
//			$x_7 = $inputExData["x_7"][0]["C_INPUT_CONTENT"];
//			$x_7_id = $inputExData["x_7"][0]["N_REPORT_DETAIL_ID"];
//		} else if ($comKind == CK_COM_MAKE) {
//			$x_7 = $x_7_def;
//		}
//	} else {
//		// 一括発議の確認画面では、
//		// 　1.デフォルト値取得
//		// 　2.承認順序が変更されている場合は置換(instExecVw.phpで行う)
//		$x_7 = $x_7_def;
//	}

	// 発議者氏名
//	$x_8 = "";
//	$x_8_id = "";
//	if (is_array($inputExData["x_8"]) && (count($inputExData["x_8"] > 0))) {
//		$x_8 = $inputExData["x_8"][0]["C_INPUT_CONTENT"];
//		$x_8_id = $inputExData["x_8"][0]["N_REPORT_DETAIL_ID"];
//	} else if ($comKind == CK_COM_MAKE) {
//		$x_8 = $loginUser["C_SHAIN_NAME"];
//	}
//	// 一括発議時のデフォルトを「ログインユーザー」にする
//	if ($lumpFlg && $x_8 == "")	$x_8 = $loginUser["C_SHAIN_NAME"];
//
//	if ($inputData["N_RESPONSE_CATEGORY"] !== "") {
//		if ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA) {
//			if ($inputData["N_RESPONSE_TYPE"] != "") {
//				$hacchusyasyoriChk[$inputData["N_RESPONSE_TYPE"]] = " checked";
//			}
//			$hacchusyasyorisonota = $inputData["C_RESPONSE_TYPE_OTHER_DETAIL"];
//		} else {
//			if ($inputData["N_RESPONSE_TYPE"] != "") {
//				$ukeoisyoriChk[$inputData["N_RESPONSE_TYPE"]] = " checked";
//			}
//			$ukeoisyorisonota = $inputData["C_RESPONSE_TYPE_OTHER_DETAIL"];
//		}
//	}

	$respHachuFlg = false;
	$respUkeoiFlg = false;
	if ($infoEA[BCODE_RESPONSE_TYPE]) {
		// 回答実行時はログインユーザ氏名を回答者名とする。
		$respHachuFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA);
		$respUkeoiFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_UKEOISHA);
		if (($respHachuFlg || $respUkeoiFlg) && !$mdlPEExec && !$initConfFlg) {
			$inputData["C_RESPONDENT"] = $loginUser["C_SHAIN_NAME"];
		}
	}
	$respHachuDateFlg = false;
	$respUkeoiDateFlg = false;
	if ($infoEA[BCODE_RESPONSE_DATE]) {
		$respHachuDateFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA);
		$respUkeoiDateFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_UKEOISHA);
	}
	$respHachuDtFlg = false;
	$respUkeoiDtFlg = false;
	if ($infoEA[BCODE_RESPONSE_TYPE_OTHER_DETAIL]) {
		$respHachuDtFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA);
		$respUkeoiDtFlg = ($inputData["N_RESPONSE_CATEGORY"] == ROLE_UKEOISHA);
	}
	$syoriFlg = false;
	if ($infoEA[BCODE_SYORI_TYPE]) {
		$respHachuFlg = true;
		$respUkeoiFlg = true;
		$syoriFlg = true;
	}
	if ($infoEA[BCODE_SYORI_DATE]) {
		$respHachuDateFlg = true;
		$respUkeoiDateFlg = true;
	}
	if ($infoEA[BCODE_SYORI_TYPE_OTHER_DETAIL]) {
		$respHachuDtFlg = true;
		$respUkeoiDtFlg = true;
	}
	$respHachuDispFlg = false;
	$respUkeoiDispFlg = false;
	if ($respHachuFlg || $respUkeoiFlg || $respHachuDateFlg || $respUkeoiDateFlg
			|| $respHachuDtFlg || $respUkeoiDtFlg) {
		if (!$syoriFlg) {
			$respHachuDispFlg = ($respHachuFlg
				 && ($inputData["N_RESPONSE_CATEGORY"] == ROLE_HACHUSHA));
			$respUkeoiDispFlg = ($respUkeoiFlg
				 && ($inputData["N_RESPONSE_CATEGORY"] == ROLE_UKEOISHA));
		} else {
			$respHachuDispFlg = ($inputData["N_PROPONENT_TYPE"] == ROLE_HACHUSHA);
			$respUkeoiDispFlg = ($inputData["N_PROPONENT_TYPE"] == ROLE_UKEOISHA);
		}
	}

	if ($allEditFlg) {
		$respHachuFlg = true;
		$respUkeoiFlg = true;
		$respHachuDateFlg = true;
		$respUkeoiDateFlg = true;
		$respHachuDtFlg = true;
		$respUkeoiDtFlg = true;
		$respHachuDispFlg = true;
		$respUkeoiDispFlg = true;
	}

	if ($mdlPEExec) {
		$respHachuFlg = true;
		$respUkeoiFlg = true;
		$respHachuDateFlg = true;
		$respUkeoiDateFlg = true;
		$respHachuDtFlg = true;
		$respUkeoiDtFlg = true;
		$allEditFlg = true;
	}
	if ($initConfFlg) {
		$respHachuDateFlg = true;
		$respUkeoiDateFlg = true;
	}

	// 処理・回答年月日(回答)　年月日
	$hDate = ($inputData["N_RESPONSE_CATEGORY"] != ROLE_UKEOISHA) ? $inputData["D_RESPONSE_DATE"] : $inputData["D_SYORI_DATE"];
	$hAttr = [];
	$hAttr['name'] = CMainFuncCtrl::setNengoString(PN_REPMINUTE_RESP_DATE);
	$hAttr['id'] = $hAttr['name'];
$respHachuDateFlg = TRUE; //TEST
	$hacchusyadate = CMainFuncCtrl::circularDateSet(
		$hDate,
		$respHachuDateFlg,
		$hAttr
	);

	// 処理・回答-受注者年月日
//	$uDate = ($inputData["N_RESPONSE_CATEGORY"] != ROLE_UKEOISHA) ? $inputData["D_SYORI_DATE"] : $inputData["D_RESPONSE_DATE"];
//	$uAttr = [];
//	$uAttr['name'] = CMainFuncCtrl::setNengoString(PN_REPMINUTE_RESP_DATE . "_2");
//	$uAttr['id'] = $uAttr['name'];
//	$ukeoidate = CMainFuncCtrl::circularDateSet(
//		$uDate,
//		$respUkeoiDateFlg,
//		$uAttr
//	);
?>
<style type="text/css">
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
	font: normal 12px /1.3em "ＭＳ ゴシック",Osaka,sans-serif;
	color: #3f3f3f;
}
input{
	height: 14px;
}
textarea {
<?php if (!isFireFox()) { ?>
	overflow-x: hidden;
<?php } ?>
	overflow-y: scroll;
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

table td.r {
	width: 25%;
}
table td.r label {
	margin-right: 2em;
}

.initTypeSet {
	margin: 1em 0 1em 1em;
}
.m_detail {
	width: 520px;
	height: 240px;
	font: normal 12px /1.3em "ＭＳ ゴシック",Osaka,sans-serif;
	margin: 4px 0 4px 20px;
}
/*#minutes_detail{*/
/*    width: 535px;*/
/*    */<?// if (isChrome()) : ?>/*height: 230px;*/<?// endif ?>
/*    */<?// if (! isChrome()) : ?>/*height: 234px;*/<?// endif ?>
/*	overflow-x: hidden;*/
/*	overflow-y: scroll;*/
/*	resize: none;*/
/*	margin: 4px 0 4px 20px;*/
/*}*/
#divbody table td table.noneborder td,
#report_space table td table.noneborder td {
	border: none;
}

td.nochk {
	vertical-align: bottom;
}

#divbody table td table.noneborder td.kakko,
#report_space table td table.noneborder td.kakko{
	padding: 0;
	margin: 0;
	vertical-align: bottom;
}

.kakko img{
	float:left;
}
.resp_detail {
	width: 520px;
	height: 240px;
	font: normal 12px /1.3em "ＭＳ ゴシック",Osaka,sans-serif;
	margin: 0 0 0 20px;
}

/*.kakko textarea {*/
/*	width: 535px;*/
/*    */<?// if (isChrome()) : ?>/*height: 230px;*/<?// endif ?>
/*    */<?// if (! isChrome()) : ?>/*height: 234px;*/<?// endif ?>
/*	overflow-x: hidden;*/
/*	overflow-y: scroll;*/
/*	resize: none;*/
/*	margin: 0 0 0 20px;*/
/*}*/

table th.tate2, table th.tate {
	border: 1px solid #919191;
	text-align: center;
	background: #78808f;
	color: #ffffff;
}

table#uchiawasenaiyo1 tr td table tr.smallrow td
, table#uchiawasenaiyo1 tr td table tr.largerow td.kakko {
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
	width:105px;
}
.route_btn {
	float:right;
	margin-bottom: 1em;
}
.titleSpan {
	padding-left: 10px;
}
.titleDiv {
	width: 510px;
	padding-left: 10px;
	text-align: center;
	font: normal 12px /1.3em "ＭＳ ゴシック",Osaka,sans-serif;
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
	font: normal 12px /1.3em "ＭＳ ゴシック",Osaka,sans-serif;
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
    margin-left: 70px;
}
-->
</style>
<?php
	//JavaScript非出力指定時以外は出力する。
	if (!$jsNoneFlg) {
?>
<script type="text/javascript">
<!--

function setPDFParam(fromfrm, sendfrm, chkFlg){
 
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
 
	<?php if (true): ?> // TESTTTTTTT
<!--	--><?php //if ($infoEA[BCODE_RESPONSE_DATE]): ?>
    var check_name = '<?= PN_REPMINUTE_RESP_DATE ?>';
    var check_object = createCheckDateObject(fromfrm, check_name, '処理・回答年月日(回答)');
    if (invalidFormDate(check_object)) return false;
	<?php endif; ?>
    
    if (!chkFlg) {
        // 内容
        if (checkElemType(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, "textarea")){
            jsHanToZen(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>);
            if (!checkTextAreaSize(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, 86, 13)){
                if (!confirm("内容 の入力数が多い為、PDFに表示されない文字があります。")){
                    alert("1行は全角43文字(半角86文字)まで、行数は13行までで入力して"
                        + "ください。");
                    fromfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>.focus();
                    return false;
                }
            }
        }
        // 処理・回答　その他　内容(回答)
        if (checkElemType(fromfrm.c_response_type_other_detail, "textarea")){
            jsHanToZen(fromfrm.c_response_type_other_detail);
            if (!checkTextAreaSize(fromfrm.c_response_type_other_detail, 86, 13)){
                if (!confirm("処理・回答　その他　内容(回答) の入力数が多い為、PDFに表示されない文字があります。")){
                    alert("1行は全角43文字(半角86文字)まで、行数は13行までで入力して"
                        + "ください。");
                    fromfrm.c_response_type_other_detail.focus();
                    return false;
                }
            }
        }

        // 工事名
        if (checkElemType(fromfrm.x_1, "textarea")){
            jsHanToZen(fromfrm.x_1);
            if (!checkTextAreaSize(fromfrm.x_1, 32, 5)){
                if (!confirm("工事名 の入力数が多い為、PDFに表示されない文字があります。")){
                    alert("1行は全角16文字(半角32文字)まで、行数は5行までで入力して"
                        + "ください。");
                    fromfrm.x_1.focus();
                    return false;
                }
            }
        }

        // 宛先役割
        if (checkElemType(fromfrm.x_2, "textarea")){
            jsHanToZen(fromfrm.x_2);
            if (!checkTextAreaSize(fromfrm.x_2, 20, 1)){
                if (!confirm("宛先役割 の入力数が多い為、PDFに表示されない文字があります。")){
                    alert("1行は全角10文字(半角20文字)まで、行数は1行までで入力して"
                        + "ください。");
                    fromfrm.x_2.focus();
                    return false;
                }
            }
        }

        // 受注者
        if (checkElemType(fromfrm.x_3, "textarea")){
            jsHanToZen(fromfrm.x_3);
            if (!checkTextAreaSize(fromfrm.x_3, 20, 3)){
                if (!confirm("受注者 の入力数が多い為、PDFに表示されない文字があります。")){
                    alert("1行は全角10文字(半角20文字)まで、行数は3行までで入力して"
                        + "ください。");
                    fromfrm.x_3.focus();
                    return false;
                }
            }
        }
    }
    else {
        // 内容
        if(checkElemType(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, "textarea")) {
            jsHanToZen(fromfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>);
            if (!chkTextAreaSize(fromfrm.<?php echo PN_REPMINUTE_DETAIL;?>, 86, 13, "内容")){
                fromfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>.focus();
                return false;
            }
        }

        //処理・回答　その他　内容(回答)
        if (checkElemType(fromfrm.c_response_type_other_detail, "textarea")) {
            jsHanToZen(fromfrm.c_response_type_other_detail);
            if (!chkTextAreaSize(fromfrm.c_response_type_other_detail, 86, 13, "処理・回答内容")){
                fromfrm.c_response_type_other_detail.focus();
                return false;
            }
        }
        
        // 工事名
        if(checkElemType(fromfrm.x_1, "textarea")) {
            jsHanToZen(fromfrm.x_1);
            if (!chkTextAreaSize(fromfrm.x_1, 32, 5, "工事名")){
                fromfrm.x_1.focus();
                return false;
            }
        }

        // 宛先役割
        if(checkElemType(fromfrm.x_2, "textarea")) {
            jsHanToZen(fromfrm.x_2);
            if (!chkTextAreaSize(fromfrm.x_2, 20, 1, "宛先役割")){
                fromfrm.x_2.focus();
                return false;
            }
        }

        // 受注者
        if(checkElemType(fromfrm.x_3, "textarea")) {
            jsHanToZen(fromfrm.x_3);
            if (!chkTextAreaSize(fromfrm.x_3, 20, 3, "受注者")){
                fromfrm.x_3.focus();
                return false;
            }
        }
        
        // 発議者役割
        if (!checkElemType(fromfrm.x_5, "hidden")) {
            jsHanToZen(fromfrm.x_5);
            if (InputCheck(fromfrm.x_5, 0, 40, 0, "発議者役割", 6) == false){
                fromfrm.x_5.focus();
                return false;
            }
        }

        // 工事場所
        if (!checkElemType(fromfrm.x_6, "hidden")) {
            jsHanToZen(fromfrm.x_6);
            if (InputCheck(fromfrm.x_6, 0, 80, 0, "工事場所", 6) == false){
                fromfrm.x_6.focus();
                return false;
            }
        }

        // 宛先
        if (!checkElemType(fromfrm.x_7, "hidden")) {
            jsHanToZen(fromfrm.x_7);
            if (InputCheck(fromfrm.x_7, 0, 20, 0, "宛先", 6) == false){
                fromfrm.x_7.focus();
                return false;
            }
        }

        // 契約番号
        if (!checkElemType(fromfrm.x_8, "hidden")) {
            jsHanToZen(fromfrm.x_8);
            if (InputCheck(fromfrm.x_8, 0, 20, 0, "契約番号", 6) == false){
                fromfrm.x_8.focus();
                return false;
            }
        }

        // 発議者氏名
        if (!checkElemType(fromfrm.x_10, "hidden")) {
            jsHanToZen(fromfrm.x_10);
            if (InputCheck(fromfrm.x_10, 0, 40, 0, "発議者氏名", 6) == false){
                fromfrm.x_10.focus();
                return false;
            }
        }

        // 監理事務所名
        if (!checkElemType(fromfrm.x_14, "hidden")) {
            jsHanToZen(fromfrm.x_14);
            if (InputCheck(fromfrm.x_14, 0, 76, 0, "監理事務所名", 6) == false){
                fromfrm.x_14.focus();
                return false;
            }
        }

        // 監理事務所名
        if (!checkElemType(fromfrm.x_14, "hidden")) {
            jsHanToZen(fromfrm.x_14);
            if (InputCheck(fromfrm.x_14, 0, 20, 0, "監理事務所名", 6) == false){
                fromfrm.x_14.focus();
                return false;
            }
        }

        // 建築立会人
        if (!checkElemType(fromfrm.x_15, "hidden")) {
            jsHanToZen(fromfrm.x_15);
            if (InputCheck(fromfrm.x_15, 0, 20, 0, "建築立会人", 6) == false){
                fromfrm.x_15.focus();
                return false;
            }
        }

        // 電気立会人
        if (!checkElemType(fromfrm.x_16, "hidden")) {
            jsHanToZen(fromfrm.x_16);
            if (InputCheck(fromfrm.x_16, 0, 20, 0, "電気立会人", 6) == false){
                fromfrm.x_16.focus();
                return false;
            }
        }

        // 機械立会人
        if (!checkElemType(fromfrm.x_17, "hidden")) {
            jsHanToZen(fromfrm.x_17);
            if (InputCheck(fromfrm.x_17, 0, 20, 0, "機械立会人", 6) == false){
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
				if (!focusElement)	var focusElement = fromfrm.<?= PN_REPMINUTE_PROP_DATE ?>;
			}
<?php endif; ?>
			// 処理・回答
			//var conf_orderer = false;
			//var conf_contractor = false;
			//// ワンレス発議時は、処理・回答の確認なし
			//if (!('<?//= $odrExec ?>//' == '1')) {
			//	// 承認時のみ
			//	if (parseInt('<?//= $iRouteID ?>//', 10) > 1) {
			//		// 発注者-処理・回答を確認するか判定
			//		if (fromfrm.<?//= PN_REPMINUTE_RESP_CAT ?>//.value == '<?//= ROLE_HACHUSHA ?>//')	conf_orderer = true;
			//		// 受注者-処理・回答を確認するか判定
			//		if (fromfrm.<?//= PN_REPMINUTE_RESP_CAT ?>//.value == '<?//= ROLE_UKEOISHA ?>//')	conf_contractor = true;
			//	}
			//}
<?php if ($respHachuDateFlg): ?>
			// 発注者-処理・回答
			//if (conf_orderer) {
			//	// 事項
			//	var items = fromfrm.n_response_type;
			//	var selFlg = false;
			//	for (var i = 0; i < items.length; i++) {
			//		var target = items[i];
			//		if (target.checked) {
			//			selFlg = true;
			//			break;
			//		}
			//	}
			//	if (!selFlg) {
			//		execConf = true;
			//		inputList += '\n　処理・回答事項';
			//		if (!focusElement)	var focusElement = fromfrm.n_response_type_1;
			//	}
			//	// 年月日
			//	var $<?//= PN_REPMINUTE_RESP_DATE ?>//_1 = $(fromfrm).find('[name="<?//= PN_REPMINUTE_RESP_DATE ?>//"]');
			//	if ($<?//= PN_REPMINUTE_RESP_DATE ?>//_1.val() == '') {
			//		execConf = true;
			//		inputList += '\n　処理・回答年月日';
			//		if (!focusElement)	var focusElement = fromfrm.<?//= PN_REPMINUTE_RESP_DATE ?>//_1;
			//	}
			//}
<?php endif; ?>
<?php if ($respUkeoiDateFlg): ?>
			// 受注者-処理・回答
			//if (conf_contractor) {
			//	// 事項
			//	var items = fromfrm.response_type_2;
			//	var selFlg = false;
			//	for (var i = 0; i < items.length; i++) {
			//		var target = items[i];
			//		if (target.checked) {
			//			selFlg = true;
			//			break;
			//		}
			//	}
			//	if (!selFlg) {
			//		execConf = true;
			//		inputList += '\n　処理・回答事項';
			//		if (!focusElement)	var focusElement = fromfrm.response_type_2_1;
			//	}
			//	// 年月日
			//	var $<?//= PN_REPMINUTE_RESP_DATE ?>// = $(fromfrm).find('[name="<?//= PN_REPMINUTE_RESP_DATE ?>//"]');
			//	if ($<?//= PN_REPMINUTE_RESP_DATE ?>//.val() == '') {
			//		execConf = true;
			//		inputList += '\n　処理・回答年月日';
			//		if (!focusElement)	var focusElement = fromfrm.<?//= PN_REPMINUTE_RESP_DATE ?>//_1;
			//	}
			//}
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
		if (!chkSignColText(fromfrm, sendfrm, chkFlg))	return false;
	} else if (this.chkCustomSignColText) {
		if (!chkCustomSignColText(fromfrm, sendfrm, chkFlg))	return false;
	}

	// 入力値移動
	// 発議者
	//sendfrm.n_proponent_type_1.value = "";
	//sendfrm.n_proponent_type_2.value = "";
	//var radioFlg = false;
	//try {
	//	radioFlg = ((fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//.length)
	//		&& checkElemType(fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[0], "checkbox"));
	//} catch (e) {
	//	radioFlg = false;
	//}
	//if (radioFlg) {
	//	var selFlg = true;
	//	if (fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[0].checked) {
	//		sendfrm.n_proponent_type_1.value = 1;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_CAT); ?>//.value
	//			 = fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[1].value;
	//	} else if (fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[1].checked) {
	//		sendfrm.n_proponent_type_2.value = 1;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_CAT); ?>//.value
	//			 = fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[0].value;
	//	} else {
	//		selFlg = false;
	//	}
	//	if (chkFlg) {
	//		if (!selFlg) {
	//			alert("発議者を選択してください。");
	//			fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//[0].focus();;
	//			return false;
	//		}
	//	}
	//} else {
	//	switch (fromfrm.<?php //echo(PN_REPMINUTE_PROP_TYPE); ?>//.value) {
	//		case "<?php //echo(ROLE_HACHUSHA); ?>//":
	//			sendfrm.n_proponent_type_1.value = 1;
	//			fromfrm.<?php //echo(PN_REPMINUTE_RESP_CAT); ?>//.value
	//				 = "<?php //echo(ROLE_UKEOISHA); ?>//";
	//		break;
	//		case "<?php //echo(ROLE_UKEOISHA); ?>//":
	//			sendfrm.n_proponent_type_2.value = 1;
	//			fromfrm.<?php //echo(PN_REPMINUTE_RESP_CAT); ?>//.value
	//				 = "<?php //echo(ROLE_HACHUSHA); ?>//";
	//		break;
	//	}
	//}

	// 発議年月日
	//var $<?//= (PN_REPMINUTE_PROP_DATE) ?>//     = $(fromfrm).find('[name="<?//= (PN_REPMINUTE_PROP_DATE) ?>//"]');
	//var $<?//= (PN_REPMINUTE_PROP_DATE) ?>//_pdf = $(sendfrm).find('[name="<?//= (PN_REPMINUTE_PROP_DATE) ?>//"]');
	//if ($<?//= (PN_REPMINUTE_PROP_DATE) ?>//.val() == '') {
	//	$<?//= (PN_REPMINUTE_PROP_DATE) ?>//_pdf.val('');
	//} else {
	//	$<?//= (PN_REPMINUTE_PROP_DATE) ?>//_pdf.val(getWarekiFormat($<?//= (PN_REPMINUTE_PROP_DATE) ?>//.val(), 'D'));
	//}

    // 発議年月日
    var prop_date = $(fromfrm).find('input[name=<?php echo PN_REPMINUTE_PROP_DATE ?>]').val();
    sendfrm.<?php echo PN_REPMINUTE_PROP_DATE ?>.value = getWarekiFormat(prop_date, 'D');

    // 工期-開始日
    var x11_date = $(fromfrm).find('input[name=x_11]').val();
    sendfrm.x_11.value = getWarekiFormat(x11_date, 'D');

    // 工期-終了日
    var x12_date = $(fromfrm).find('input[name=x_12]').val();
    sendfrm.x_12.value = getWarekiFormat(x12_date, 'D');

    // 工事着手日
    var x13_date = $(fromfrm).find('input[name=x_13]').val();
    sendfrm.x_13.value = getWarekiFormat(x13_date, 'D');

    // 指示日
    var x18_date = $(fromfrm).find('input[name=x_18]').val();
    sendfrm.x_18.value = getWarekiFormat(x18_date, 'D');

    // 処理・回答年月日(回答)
    var res_date = $(fromfrm).find('input[name=<?php echo PN_REPMINUTE_RESP_DATE ?>]').val();
    sendfrm.<?php echo PN_REPMINUTE_RESP_DATE ?>.value = getWarekiFormat(res_date, 'D');

	// 発議事項
	sendfrm.n_initiation_type_1.value = "";
	sendfrm.n_initiation_type_2.value = "";
	sendfrm.n_initiation_type_3.value = "";
	sendfrm.n_initiation_type_4.value = "";
	sendfrm.n_initiation_type_5.value = "";
	radioFlg = false;
	var initRepFlg = true;
	try {
		radioFlg = ((fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>.length)
			&& (checkElemType(fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[0], "checkbox")));
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
				fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>[0].focus();;
				return false;
			}
		}
	} else {
		switch (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE); ?>.value) {
			case "<?php echo(MINUTE_INITTYPE_INSTRUCT); ?>":
				sendfrm.n_initiation_type_1.value = 1;
			break;
			case "<?php echo(MINUTE_INITTYPE_AGREE); ?>":
				sendfrm.n_initiation_type_2.value = 1;
			break;
			case "<?php echo(MINUTE_INITTYPE_CONFER); ?>":
				sendfrm.n_initiation_type_3.value = 1;
			break;
			case "<?php echo(MINUTE_INITTYPE_PRESENT); ?>":
				sendfrm.n_initiation_type_4.value = 1;
			break;
			case "<?php echo(MINUTE_INITTYPE_REPORT); ?>":
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
        radioFlg = ((fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>.length)
    && (checkElemType(fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[0], "checkbox")));
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
                fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>[0].focus();;
                return false;
            }
        }
    } else {
        switch (fromfrm.<?php echo(PN_REPMINUTE_INIT_TYPE . '_2'); ?>.value) {
        case "<?php echo(MINUTE_INITTYPE_INSTRUCT); ?>":
            sendfrm.n_initiation_type_2_1.value = 1;
            break;
        case "<?php echo(MINUTE_INITTYPE_AGREE); ?>":
            sendfrm.n_initiation_type_2_2.value = 1;
            break;
        case "<?php echo(MINUTE_INITTYPE_CONFER); ?>":
            sendfrm.n_initiation_type_2_3.value = 1;
            break;
        case "<?php echo(MINUTE_INITTYPE_PRESENT); ?>":
            sendfrm.n_initiation_type_2_4.value = 1;
            break;
        case "<?php echo(MINUTE_INITTYPE_REPORT); ?>":
            sendfrm.n_initiation_type_2_5.value = 1;
            break;
        }
    }
	

	// 内容
	sendfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>.value = fromfrm.<?php echo(PN_REPMINUTE_DETAIL); ?>.value;
	
	// 工事名
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
	// 工期 開始日
	// var $x_5     = $(fromfrm).find('[name="x_5"]');
	// var $x_5_pdf = $(sendfrm).find('[name="x_5"]');
	// if ($x_5.val() == '') {
	// 	$x_5_pdf.val('');
	// } else {
	// 	$x_5_pdf.val(getWarekiFormat($x_5.val(), 'D'));
	// }
	// // 工期 終了日
	// var $x_6     = $(fromfrm).find('[name="x_6"]');
	// var $x_6_pdf = $(sendfrm).find('[name="x_6"]');
	// if ($x_6.val() == '') {
	// 	$x_6_pdf.val('');
	// } else {
	// 	$x_6_pdf.val(getWarekiFormat($x_6.val(), 'D'));
	// }
 
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
    sendfrm.c_response_type_other_detail.value = fromfrm.c_response_type_other_detail.value;

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
		// sel_resptype_1 = fromfrm.n_response_type.value;
	}
	//sendfrm.c_c_response_type_other_detail.value
	//	 = fromfrm.c_response_type_other_detail.value;
	//var $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1     = $(fromfrm).find('[name="<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1"]');
	//var $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1_pdf = $(sendfrm).find('[name="<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1"]');
	//if ($<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1.val() == '') {
	//	$<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1_pdf.val('');
	//} else {
	//	$<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1_pdf.val(getWarekiFormat($<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1.val(), 'D'));
	//}

	// 処理・回答-内容 ※発注者発議
	//sendfrm.n_response_type_2_1.value = "";
	//sendfrm.n_response_type_2_2.value = "";
	//sendfrm.n_response_type_2_3.value = "";
	//sendfrm.n_response_type_2_4.value = "";
	//var sel_resptype_2 = "";
	//radioFlg = false;
	//try {
	//	radioFlg = ((fromfrm.response_type_2.length)
	//		&& (checkElemType(fromfrm.response_type_2[0], "checkbox")));
	//} catch (e) {
	//	radioFlg = false;
	//}
	//if (radioFlg) {
	//	var selFlg = true;
	//	if (fromfrm.response_type_2[0].checked) {
	//		sendfrm.n_response_type_2_1.value = 1;
	//		sel_resptype_2 = fromfrm.response_type_2[0].value;
	//	} else if (fromfrm.response_type_2[1].checked) {
	//		sendfrm.n_response_type_2_2.value = 1;
	//		sel_resptype_2 = fromfrm.response_type_2[1].value;
	//	} else if (fromfrm.response_type_2[2].checked) {
	//		sendfrm.n_response_type_2_3.value = 1;
	//		sel_resptype_2 = fromfrm.response_type_2[2].value;
	//	} else if (fromfrm.response_type_2[3].checked) {
	//		sendfrm.n_response_type_2_4.value = 1;
	//		sel_resptype_2 = fromfrm.response_type_2[3].value;
	//	} else if (!fromfrm.response_type_2[0].disabled){
	//		selFlg = false;
	//	}
	//} else {
	//	switch (fromfrm.response_type_2.value) {
	//		case "<?php //echo(MINUTE_RESPONSE_CONSENT); ?>//":
	//			sendfrm.n_response_type_2_1.value = 1;
	//		break;
	//		case "<?php //echo(MINUTE_RESPONSE_AGREE); ?>//":
	//			sendfrm.n_response_type_2_2.value = 1;
	//		break;
	//		case "<?php //echo(MINUTE_RESPONSE_INSTRUCT); ?>//":
	//			sendfrm.n_response_type_2_3.value = 1;
	//		break;
	//		case "<?php //echo(MINUTE_RESPONSE_ACCEPT); ?>//":
	//			sendfrm.n_response_type_2_4.value = 1;
	//		break;
	//	}
	//	sel_resptype_2 = fromfrm.response_type_2.value;
	//}
	
	
	// 	 = fromfrm.response_type_other_detail_2_dum.value;
	//var $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2     = $(fromfrm).find('[name="<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2"]');
	//var $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2_pdf = $(sendfrm).find('[name="<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2"]');
	//if ($<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2.val() == '') {
	//	$<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2_pdf.val('');
	//} else {
	//	$<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2_pdf.val(getWarekiFormat($<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2.val(), 'D'));
	//}

	// 処理・回答
	//switch (fromfrm.<?php //echo(PN_REPMINUTE_RESP_CAT); ?>//.value) {
	//	case "<?php //echo(ROLE_HACHUSHA); ?>//":
	//		// 回答
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_TYPE); ?>//.value = sel_resptype_1;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_OTEHRD); ?>//.value = sendfrm.c_c_response_type_other_detail.value;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_DATE); ?>//.value = $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1.val();
	//		// 処理
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_DATE); ?>//.value = $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2.val();
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_TYPE); ?>//.value = sel_resptype_2;
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_OTEHRD); ?>//.value = sendfrm.c_response_type_other_detail.value;
	//	break;
	//	case "<?php //echo(ROLE_UKEOISHA); ?>//":
	//		// 回答
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_TYPE); ?>//.value = sel_resptype_2;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_OTEHRD); ?>//.value = sendfrm.c_response_type_other_detail.value;
	//		fromfrm.<?php //echo(PN_REPMINUTE_RESP_DATE); ?>//.value = $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_2.val();
	//		// 処理
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_DATE); ?>//.value = $<?//= (PN_REPMINUTE_RESP_DATE) ?>//_1.val();
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_TYPE); ?>//.value = sel_resptype_1;
	//		fromfrm.<?php //echo(PN_REPMINUTE_SYORI_OTEHRD); ?>//.value = sendfrm.c_c_response_type_other_detail.value;
	//	break;
	//}

	if (!this.setCustomSignPdfInfo && this.setSignPdfInfo)	setSignPdfInfo(sendfrm);
	else if (this.setCustomSignPdfInfo)	setCustomSignPdfInfo(sendfrm);

	var setAttrObj = sendfrm.<?php echo(PN_PDF_SETATTR); ?>;
	if (setAttrObj != "") {
		setAttrStr = setAttrObj.value;
		setAttrObj.value= setAttrStr;
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
 * @param string clsName	同じグループ名
 * @param mixed value 選択値
 */
function onRadioCheckboxClicked(el, clsName){
    var class_name = el.className;
    if (class_name && class_name.indexOf('initiation_type') > -1) {
        $('input[name^=n_initiation_type]').not('.' + class_name).prop('checked', false);
        $('.' + class_name).prop('checked', el.checked);
    }
	var p = new RegExp("(^|\\s)"+clsName+"(\\s|$)");
	var chks = document.getElementsByTagName("input");
	for (var i=0; i<chks.length; i++){
		if (el== chks[i] || !p.test(chks[i].className)) continue;
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
function enableFormElsByClass(clsName, tags, enabled){
	var p = new RegExp("(^|\\s)"+clsName+"(\\s|$)");
	var replaceEditClsP = /(^|\s)(?:editable)|(?:disable)(\s|$)/g;
	var newEditClsName = enabled? "$1editable$2" : "$1disable$2";
	for (var i=0; i<tags.length; i++){
		var els = document.getElementsByTagName(tags[i]);
		for (var j=0; j<els.length; j++){
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

	if ("<?php echo($allEditFlg); ?>" == "")	return;

	//処理・回答の表示切替とデータ移動
	var prop1El = document.getElementById("proponent_type_1");
	var prop2El = document.getElementById("proponent_type_2");
	var selClient = prop1El.checked ? 1 : (prop2El.checked ? 0 : -1);
	var selContractor = prop1El.checked ? 0 : (prop2El.checked ? 1 : -1);
	if (selClient != -1 && selContractor != -1) {
		var srcIdx = 0;
		var dstIdx = 0;
		var exchgTxtEls = [
			["c_response_type_other_detail", "response_type_other_detail_2"],
			["<?= (PN_REPMINUTE_RESP_DATE) ?>_1_1", "<?= (PN_REPMINUTE_RESP_DATE) ?>_2_1"],
			["<?= (PN_REPMINUTE_RESP_DATE) ?>_1_2", "<?= (PN_REPMINUTE_RESP_DATE) ?>_2_2"],
			["<?= (PN_REPMINUTE_RESP_DATE) ?>_1_3", "<?= (PN_REPMINUTE_RESP_DATE) ?>_2_3"],
		];
		var exchgChkEls = [
			["n_response_type","response_type_2_1"],
			["n_response_type_2","response_type_2_2"],
			["n_response_type_3","response_type_2_3"],
			["n_response_type_4","response_type_2_4"]
		];

		//表示切替
		if (selClient == 1) {
			if (!$('#respClient').hasClass('hide')) {
				$('#respClient').addClass('hide');
			}
			if ($('#respContractor').hasClass('hide')) {
				$('#respContractor').removeClass('hide');
			}
		} else if (selContractor == 1) {
			if (!$('#respContractor').hasClass('hide')) {
				$('#respContractor').addClass('hide');
			}
			if ($('#respClient').hasClass('hide')) {
				$('#respClient').removeClass('hide');
			}
		}

		//データ移動
		srcIdx = selContractor;
		dstIdx = selClient;
		for (var i = 0; i < exchgTxtEls.length; i++) {
			var srcEl = document.getElementById(exchgTxtEls[i][srcIdx]);
			var dstEl = document.getElementById(exchgTxtEls[i][dstIdx]);
			if (srcEl.value == "") continue;
			dstEl.value = srcEl.value;
			srcEl.value = "";
		}
		for (var i = 0; i < exchgChkEls.length; i++) {
			var srcEl = document.getElementById(exchgChkEls[i][srcIdx]);
			var dstEl = document.getElementById(exchgChkEls[i][dstIdx]);
			if (!srcEl.checked) continue;
			dstEl.checked = srcEl.checked;
			srcEl.checked = false;
		}
	}

	if (prop1El.checked && !prop2El.checked) {
		$('#<?= (PN_REPMINUTE_RESP_DATE) ?>_2_nengo').val($('#<?= (PN_REPMINUTE_RESP_DATE) ?>_1_nengo').val());
	} else if (!prop1El.checked && prop2El.checked) {
		$('#<?= (PN_REPMINUTE_RESP_DATE) ?>_1_nengo').val($('#<?= (PN_REPMINUTE_RESP_DATE) ?>_2_nengo').val());
	}
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
//-->
</script>
<?php
	//JavaScript非出力指定時以外は出力する。
	}

	echo"			<table class=\"func-tbl\" summary=\"指示・承諾・協議・提出・報告書\" id=\"uchiawasenaiyo1\">\n";
	echo"				<tr>\n";
	echo"					<td colspan=\"8\" class=\"formno\">第１２号様式</td>\n";
	echo"				</tr>\n";
	echo"				<tr>\n";
	echo"					<td colspan=\"8\" class=\"formname\">";
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
	echo"						<label for=\"initiation_type_1\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_INSTRUCT], $js, $hide) . "指示   ・   </label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_2",
		"value" => MINUTE_INITTYPE_AGREE,
		'class' => 'initiation_type_2_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_AGREE], $js, array()) . "承諾   ・   </label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_3",
		"value" => MINUTE_INITTYPE_CONFER,
		'class' => 'initiation_type_3_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_3\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_CONFER], $js, array()) . "協議    ・   </label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_4",
		"value" => MINUTE_INITTYPE_PRESENT,
		'class' => 'initiation_type_4_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_4\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_PRESENT], $js, array()) . "提出   ・   </label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_5",
		"value" => MINUTE_INITTYPE_REPORT,
		'class' => 'initiation_type_5_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_5\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_REPORT], $js, array()) . "報告</label>";

	echo"　事\n";
	echo"					</td>\n";
	echo"				</tr>\n";

	// 可変押印出力
	$signsInfo_pdf = $signsInfo;
	echo"				<tr>\n";
	echo"					<td class=\"embSignArea\" colspan=\"8\">\n";
	echo"						<div class=\"signArea\">\n";
	echoSignTable($signsInfo, $loginUser, $peExec, "", false, "", $canBeUnion);
	echo"						</div>\n";
	echo"					</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td class='td-x2-x7' colspan=\"3\" nowrap>\n";
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
	echo"						<div class=\"x7\">";
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
echo"<span id=\"text_atesaki\">" . $textAtesaki . "</span>\n";
	echo"</div>";
	
	echo"					</td>\n";

	echo"					<td class=\"td-propose-date-title input-title\" nowrap> 発年<br>月<br>議日</td>\n";
	echo"					<td class=\"td-propose-date r\" nowrap>\n";
	// 発議年月日
	echo"						";
	$dateOutFlg = ($infoEA[BCODE_PROPOSE_DATE]
		 || (!$infoEA[BCODE_PROPOSE_DATE] && ($hatsugidate['wyear'] != "")
			 && ($hatsugidate['mon'] != "") && ($hatsugidate['mday'] != "")));
	if ($dateOutFlg)	echo $hatsugidate['nengo'];							// 元号
	$attr = array();
	$attr["id"] = PN_REPMINUTE_PROP_DATE . "_1";
	$attr["name"] = PN_REPMINUTE_PROP_DATE . "_1";
	$attr["value"] = $hatsugidate['wyear'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('" . PN_REPMINUTE_PROP_DATE . "_2', event);\"";
	echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);	// 年
	if ($dateOutFlg)	echo "年";
	$attr = array();
	$attr["id"] = PN_REPMINUTE_PROP_DATE . "_2";
	$attr["name"] = PN_REPMINUTE_PROP_DATE . "_2";
	$attr["value"] = $hatsugidate['mon'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('" . PN_REPMINUTE_PROP_DATE . "_3', event);\"";
	echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);	// 月
	if ($dateOutFlg)	echo "月";
	$attr = array();
	$attr["id"] = PN_REPMINUTE_PROP_DATE . "_3";
	$attr["name"] = PN_REPMINUTE_PROP_DATE . "_3";
	$attr["value"] = $hatsugidate['mday'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = "";
	echo getInputTextItem($infoEA[BCODE_PROPOSE_DATE], $attr, $js, true);	// 日
	if ($dateOutFlg)	echo "日\n";
	else	echo("&nbsp;\n");
	echo"					</td>\n";

	echo"					<td class=\"td-x5-x1-title input-title\" nowrap>発<br>議<br>者</td>\n";
	echo"					<td class='td-x5-x10' nowrap colspan='2'>\n";
//	$attr = array();
//	$attr["id"] = "proponent_type_1";
//	$attr["name"] = PN_REPMINUTE_PROP_TYPE;
//	$attr["value"] = ROLE_HACHUSHA;
//	$attr["class"] = "g_prop_type";
//	$js = " onClick=\"prop_radioCheckbox(this)\"";
//	$hide = array();
//	$hide["name"] = PN_REPMINUTE_PROP_TYPE;
//	$hide["value"] = $inputData["N_PROPONENT_TYPE"];
//	echo"						<label class=\"labelr\" for=\"proponent_type_1\">"
//		. getInputCheckboxItem($infoEA[BCODE_PROPONENT_TYPE], $attr
//			, $hatsugisyaChk[ROLE_HACHUSHA], $js, $hide) . "岐阜県</label>\n";
//	echo"						<br>\n";
//	$attr = array();
//	$attr["id"] = "proponent_type_2";
//	$attr["name"] = PN_REPMINUTE_PROP_TYPE;
//	$attr["value"] = ROLE_UKEOISHA;
//	$attr["class"] = "g_prop_type";
//	$js = " onClick=\"prop_radioCheckbox(this)\"";
//	$hide = array();
//	echo"						<label class=\"labelr\" for=\"proponent_type_2\">"
//		. getInputCheckboxItem($infoEA[BCODE_PROPONENT_TYPE], $attr
//			, $hatsugisyaChk[ROLE_UKEOISHA], $js, array()) . "受注者</label>\n";
//	echo"					</td>\n";
//	echo"					<td id=\"input_prop\" nowrap>";
//	$attr = array();
//	$attr["id"] = "x_8";
//	$attr["name"] = "x_8";
//	$attr["value"] = $x_8;
//	$attr["size"] = "20";
//	$attr["maxlength"] = "20";
//	$attr["class"] = "textfield";
//	$js = "";
    // 発議者役割
	$attr = array();
	$attr["id"] = "x_5";
	$attr["name"] = "x_5";
	$attr["value"] = $x_5;
	$attr["size"] = "40";
	$attr["maxlength"] = "40";
	$attr["class"] = "textfield";
	$js = "";
	echo "<div class='x5'>";
//	echo getInputTextItem($infoEA["x_5"], $attr, $js, true);
	echo getInputTextItem(true, $attr, $js, true); //TESTTTTTTTTT
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
//    echo getInputTextItem($infoEA["x_10"], $attr, $js, true);
    echo "</div>";
    echo "</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td class=\"td-x8-title input-title\" nowrap>契約番号</td>\n";
	echo"					<td  class='td-x8' nowrap colspan='2'>";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_8"], $attr, $js, true);
    echo "</span>";
	echo "</td>";
    echo"					<td class=\"td-x1-title input-title\" nowrap>工<br>事<br>名</td>\n";
    echo"					<td  class='td-x1' nowrap>";
        //工事名
        $attr = array();
        $attr["name"] = "x_1";
        $attr["id"] = "x_1";
        $attr["rows"] = "5";
        $attr["cols"] = "32";
        $js = "";
        //echo getTextAreaItem($infoEA["x_1"], $attr, $x_1, $js, true);
        echo "<span class='x1'>";
        echo getTextAreaItem(TRUE, $attr, $x_1, $js, true); //TESSTTTTTTTTTT
        echo "</span>";
    echo "</td>";
echo"					<td class=\"td-x1-title input-title\" nowrap>受<br>注<br>者</td>\n";
echo"					<td  class='td-x3' nowrap colspan='2'>";
    //受注者
    $attr = array();
    $attr["name"] = "x_3";
    $attr["id"] = "x_3";
    $attr["rows"] = "3";
    $attr["cols"] = "20";
    $js = "";
    echo "<span class='x3'>";
    //echo getTextAreaItem($infoEA["x_3"], $attr, $x_1, $js, true);
    echo getTextAreaItem(TRUE, $attr, $x_3, $js, true); //TESSTTTTTTTTTT
    echo "</span>";
echo "</td>";
//	$attr = array();
//	$attr["id"] = "x_2";
//	$attr["name"] = "x_2";
//	$attr["value"] = $x_2;
//	$attr["size"] = "64";
//	$attr["maxlength"] = "64";
//	$attr["class"] = "textfield";
//	$js = "";
//	echo getInputTextItem($infoEA["x_2"], $attr, $js, true) . "</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td class=\"td-x6-title input-title\" nowrap>工事場所</td>\n";
	echo"					<td class='td-x6' colspan=\"7\">";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_6"], $attr, $js, true);
    echo "</span>";
//	$attr = array();
//	$attr["name"] = "x_1";
//	$attr["id"] = "x_1";
//	$attr["rows"] = "2";
//	$attr["cols"] = "80";
//	$js = "";
//	if (!$infoEA["x_1"])	echo("<div class=\"kojimei\">");
//	echo getTextAreaItem($infoEA["x_1"], $attr, $x_1, $js, true);
//	if (!$infoEA["x_1"])	echo("</div>");
	echo"</td>\n";
	echo"				</tr>\n";

//	echo"				<tr>\n";
//	echo"					<td class=\"input-title\" nowrap>受注者</td>\n";
//	echo"					<td colspan=\"6\" class=\"r\" nowrap>";
//	$attr = array();
//	$attr["id"] = "x_3";
//	$attr["name"] = "x_3";
//	$attr["value"] = $x_3;
//	$attr["size"] = "80";
//	$attr["maxlength"] = "80";
//	$attr["class"] = "textfield";
//	$js = "";
//	echo getInputTextItem($infoEA["x_3"], $attr, $js, true) . "</td>\n";
//	echo"				</tr>\n";

//	echo"				<tr>\n";
//	echo"					<td class=\"input-title\" nowrap>工事場所</td>\n";
//	echo"					<td colspan=\"6\" class=\"r\" nowrap>";
//	$attr = array();
//	$attr["id"] = "x_4";
//	$attr["name"] = "x_4";
//	$attr["value"] = $x_4;
//	$attr["size"] = "80";
//	$attr["maxlength"] = "80";
//	$attr["class"] = "textfield";
//	$js = "";
//	echo getInputTextItem($infoEA["x_4"], $attr, $js, true) . "</td>\n";
//	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td class=\"input-title\" rowspan=\"2\" nowrap>工期</td>\n";
	echo"					<td id=\"text_sKoki\">自</td>\n";
	echo"					<td id=\"date_sKoki\" colspan=\"6\" class=\"td-x11 r\" nowrap>\n";
	echo"						";
	// 工期-開始日
    echo "<span class='x11'>";
	$dateOutFlg = ($infoEA["x_11"]
		 || (!$infoEA["x_11"] && ($x_11_date['wyear'] != "")
			 && ($x_11_date['mon'] != "") && ($x_11_date['mday'] != "")));
    $dateOutFlg = TRUE; //TESTTTTT
	if ($dateOutFlg)	echo $x_11_date['nengo'];			// 元号
	$attr = array();
	$attr["id"] = "x_11_1";
	$attr["name"] = "x_11_1";
	$attr["value"] = $x_11_date['wyear'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('x_11_2', event);\"";
	echo getInputTextItem($infoEA["x_11"], $attr, $js, true);	// 年
	if ($dateOutFlg)	echo "年";
	$attr = array();
	$attr["id"] = "x_11_2";
	$attr["name"] = "x_11_2";
	$attr["value"] = $x_11_date['mon'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('x_11_3', event);\"";
	echo getInputTextItem($infoEA["x_11"], $attr, $js, true);	// 月
	if ($dateOutFlg)	echo "月";
	$attr = array();
	$attr["id"] = "x_11_3";
	$attr["name"] = "x_11_3";
	$attr["value"] = $x_11_date['mday'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = "";
	echo getInputTextItem($infoEA["x_11"], $attr, $js, true);	// 日
	if ($dateOutFlg)	echo "日\n";
	else	echo("&nbsp;\n");
	echo "</span>";
	
	// 年月日入力
echo "<span class='x13'>(工事着手日: ";
    $dateOutFlg = ($infoEA["x_13"]
        || (!$infoEA["x_13"] && ($x_13_date['wyear'] != "")
            && ($x_13_date['mon'] != "") && ($x_13_date['mday'] != "")));
    $dateOutFlg = TRUE; //TESTTTTT
    if ($dateOutFlg)	echo $x_13_date['nengo'];			// 元号
    $attr = array();
    $attr["id"] = "x_13_1";
    $attr["name"] = "x_13_1";
    $attr["value"] = $x_13_date['wyear'];
    $attr["maxlength"] = "2";
    $attr["class"] = "textfield input-date";
    $js = " onKeyPress=\"go_next_field('x_13_2', event);\"";
    echo getInputTextItem($infoEA["x_13"], $attr, $js, true);	// 年
    if ($dateOutFlg)	echo "年";
    $attr = array();
    $attr["id"] = "x_13_2";
    $attr["name"] = "x_13_2";
    $attr["value"] = $x_13_date['mon'];
    $attr["maxlength"] = "2";
    $attr["class"] = "textfield input-date";
    $js = " onKeyPress=\"go_next_field('x_13_3', event);\"";
    echo getInputTextItem($infoEA["x_13"], $attr, $js, true);	// 月
    if ($dateOutFlg)	echo "月";
    $attr = array();
    $attr["id"] = "x_13_3";
    $attr["name"] = "x_13_3";
    $attr["value"] = $x_13_date['mday'];
    $attr["maxlength"] = "2";
    $attr["class"] = "textfield input-date";
    $js = "";
    echo getInputTextItem($infoEA["x_13"], $attr, $js, true);	// 日
    if ($dateOutFlg)	echo "日\n";
    else	echo("&nbsp;\n");
	echo ")</span>";
	
	
	echo"					</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td id=\"text_eKoki\">至</td>\n";
	echo"					<td id=\"date_eKoki\" colspan=\"6\" class=\"td-x12 r\" nowrap>\n";
	echo"						";
	//工期-終了日
	$dateOutFlg = ($infoEA["x_12"]
		 || (!$infoEA["x_12"] && ($x_12_date['wyear'] != "")
			 && ($x_12_date['mon'] != "") && ($x_12_date['mday'] != "")));
$dateOutFlg = TRUE; //TESTTTT
	if ($dateOutFlg)	echo $x_12_date['nengo'];				// 元号
	$attr = array();
	$attr["id"] = "x_12_1";
	$attr["name"] = "x_12_1";
	$attr["value"] = $x_12_date['wyear'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('x_12_2', event);\"";
	
	echo getInputTextItem($infoEA["x_12"], $attr, $js, true);	// 年
	if ($dateOutFlg)	echo "年";
	$attr = array();
	$attr["id"] = "x_12_2";
	$attr["name"] = "x_12_2";
	$attr["value"] = $x_12_date['mon'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = " onKeyPress=\"go_next_field('x_12_3', event);\"";
	echo getInputTextItem($infoEA["x_12"], $attr, $js, true);	// 月
	if ($dateOutFlg)	echo "月";
	$attr = array();
	$attr["id"] = "x_12_3";
	$attr["name"] = "x_12_3";
	$attr["value"] = $x_12_date['mday'];
	$attr["maxlength"] = "2";
	$attr["class"] = "textfield input-date";
	$js = "";
	echo getInputTextItem($infoEA["x_12"], $attr, $js, true);	// 日
	if ($dateOutFlg)	echo "日\n";
	else	echo("&nbsp;\n");
	echo"					</td>\n";
	echo"				</tr>\n";


    echo"				<tr>\n";
    echo"					<td class=\"td-x14-title input-title\" nowrap colspan='2'>監理事務所名</td>\n";
    echo"					<td class='td-x14' colspan=\"7\">";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_14"], $attr, $js, true);
    echo "</span>";
    //	$attr = array();
    //	$attr["name"] = "x_1";
    //	$attr["id"] = "x_1";
    //	$attr["rows"] = "2";
    //	$attr["cols"] = "80";
    //	$js = "";
    //	if (!$infoEA["x_1"])	echo("<div class=\"kojimei\">");
    //	echo getTextAreaItem($infoEA["x_1"], $attr, $x_1, $js, true);
    //	if (!$infoEA["x_1"])	echo("</div>");
    echo"</td>\n";
    echo"				</tr>\n";

    echo"				<tr>\n";
    echo '<td class="td-x15-title input-title" nowrap="" colspan="2">建  築</td>';
    echo '<td class="td-x15-title input-title" nowrap="">立  会  人</td>';
    echo"					<td  class='td-x15' nowrap colspan='4'>";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_15"], $attr, $js, true);
    echo "</span>";
    echo "</td>";

    echo '<td class="td-x15-empty" nowrap="" colspan="2"></td>';
    
    echo"					</tr>";

    echo"				<tr>\n";
    echo '<td class="td-x16-title input-title" nowrap="" colspan="2">建  築</td>';
    echo '<td class="td-x16-title input-title" nowrap="">立  会  人</td>';
    echo"					<td  class='td-x16' nowrap colspan='4'>";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_16"], $attr, $js, true);
    echo "</span>";
    echo "</td>";
    
    echo '<td class="td-x16-empty" nowrap="" colspan="2"></td>';
    
    echo"					</tr>";

    echo"				<tr>\n";
    echo '<td class="td-x17-title input-title" nowrap="" colspan="2">建  築</td>';
    echo '<td class="td-x17-title input-title" nowrap="">立  会  人</td>';
    echo"					<td  class='td-x17' nowrap colspan='4'>";
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
    echo getInputTextItem(true, $attr, $js, true); // //TESTTTTTTTTT
    //    echo getInputTextItem($infoEA["x_17"], $attr, $js, true);
    echo "</span>";
    echo "</td>";
    
    echo '<td class="td-x17-empty" nowrap="" colspan="2"></td>';
    
    echo"					</tr>";
    

	echo"				<tr>\n";
	echo"					<td id=\"row_propType\" colspan=\"8\" class=\"r\">\n";
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
	echo"						<label for=\"initiation_type_1_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_INSTRUCT], $js, $hide) . "指示</label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_2_2",
		"value" => MINUTE_INITTYPE_AGREE,
		'class' => 'initiation_type_2_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_2_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_AGREE], $js, array()) . "承諾</label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_3_2",
		"value" => MINUTE_INITTYPE_CONFER,
		'class' => 'initiation_type_3_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_3_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_CONFER], $js, array()) . "協議</label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_4_2",
		"value" => MINUTE_INITTYPE_PRESENT,
		'class' => 'initiation_type_4_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_4_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_PRESENT], $js, array()) . "提出</label>\n";

	$attr = array_merge($propItemComAttr, array(
		"id" => "initiation_type_5_2",
		"value" => MINUTE_INITTYPE_REPORT,
		'class' => 'initiation_type_5_common'
	));
	$js = " onClick=\"onRadioCheckboxClicked(this, 'g_prop_items')\"";
	echo"						<label for=\"initiation_type_5_2\">"
		. getInputCheckboxItem($infoEA[BCODE_INITIATION_TYPE], $attr
			, $hatsugijikouChk[MINUTE_INITTYPE_REPORT], $js, array()) . "報告</label>";

	echo"　事項\n";
	echo"					</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td colspan=\"8\" class=\"td-minutes_detail minutes_detail\">\n";
	$attr = array();
	$attr["name"] = PN_REPMINUTE_DETAIL;
	$attr["id"] = "minutes_detail";
	$attr["rows"] = "13";
	$attr["cols"] = "86";
	echo "						";
	if (!$infoEA[BCODE_MINUTES_DETAIL])	echo("<div class=\"m_detail\">");
	echo getTextAreaItem($infoEA[BCODE_MINUTES_DETAIL], $attr, $minutes_detail, "", true);
	if ($infoEA[BCODE_MINUTES_DETAIL])	echo("<br>");
	else	echo("</div><br>");
	echo("\n");
	echo"					</td>\n";
	echo"				</tr>\n";

	echo"				<tr>\n";
	echo"					<td class=\"td-response-title input-title\" colspan=\"8\" nowrap>処理・回答</td>\n";
	echo"				</tr>\n";

	$chkDispCls = " class=\"g_response1\"";
	$txtDispCls = " class=\"g_response1 editable textfield\"";
	$dTxtDispCls = " class=\"g_response1 editable textfield input-date\"";
	$taDispCls = " class=\"g_response1 otherta\"";
	$resCls = "";
	if ($hatsugisyaChk[ROLE_HACHUSHA] && !$hatsugisyaChk[ROLE_UKEOISHA])	$resCls = "hide";
	echo"				<tr id=\"respClient\" class=\"" . $resCls . "\">\n";
	echo"					<td colspan=\"8\" class=\"r\">\n";
	echo"						<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" summary=\"処理回答\" class=\"noneborder\">\n";
	echo"							<tr class=\"smallrow\">\n";
	echo"								<td class=\"nochk\" nowrap>上記事項については、</td>\n";
    $respHachuFlg = TRUE; //TESTTTTT
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
	echo"								<td class=\"chkfirst\"><label for=\"n_response_type_1\">"
		. getInputCheckboxItem($respHachuFlg, $attr, $hacchusyasyoriChk[MINUTE_RESPONSE_CONSENT]
			, $js, $hide) . "了解</label></td>\n";

	$attr = array_merge(array(
		"id" => "n_response_type_2",
		"value" => MINUTE_RESPONSE_AGREE,
	), $resComAttr);
	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
	echo"								<td><label for=\"n_response_type_2\">"
		. getInputCheckboxItem($respHachuFlg, $attr, $hacchusyasyoriChk[MINUTE_RESPONSE_AGREE], $js, array())
		 . "承諾</label></td>\n";

	$attr = array_merge(array(
		"id" => "n_response_type_3",
		"value" => MINUTE_RESPONSE_INSTRUCT,
	), $resComAttr);
	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
	echo"								<td><label for=\"n_response_type_3\">"
		. getInputCheckboxItem($respHachuFlg, $attr, $hacchusyasyoriChk[MINUTE_RESPONSE_INSTRUCT], $js, array())
		 . "後日指示</label></td>\n";

	$attr = array_merge(array(
		"id" => "n_response_type_4",
		"value" => MINUTE_RESPONSE_ACCEPT,
	), $resComAttr);
	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response1')\"";
	echo"								<td><label for=\"n_response_type_4\">"
		. getInputCheckboxItem($respHachuFlg, $attr, $hacchusyasyoriChk[MINUTE_RESPONSE_ACCEPT], $js, array())
		 . "受理</label></td>\n";

	echo"								<td class=\"nochk\" nowrap>する。協議のとおり施工すること。</td>\n";
	echo"							</tr>\n";
	echo"							<tr class=\"largerow\">\n";
	echo"								<td colspan=\"8\" class=\"kakko\">";
	$attr = array();
	$attr["id"] = "c_response_type_other_detail";
	$attr["name"] = "c_response_type_other_detail";
	$attr["rows"] = "13";
	$attr["cols"] = "86";
	$js = $taDispCls;
    $respHachuDtFlg = TRUE; //TESTTTTTTTTTTT
	if (!$respHachuDtFlg)	echo("<div class=\"resp_detail\">");
	echo getTextAreaItem($respHachuDtFlg, $attr, $hacchusyasyorisonota, $js, true);
	if (!$respHachuDtFlg)	echo("</div><br>");
	echo"</td>\n";
	echo"							</tr>\n";
	echo"							<tr class=\"smallrow\">\n";

// 指示日
$x_18_input = "<span class='x18'>";
$dateOutFlg = ($infoEA["x_18"]
	|| (!$infoEA["x_18"] && ($x_18_date['wyear'] != "")
		&& ($x_18_date['mon'] != "") && ($x_18_date['mday'] != "")));
$dateOutFlg = TRUE; //TESTTTTT
$infoEA["x_18"] = TRUE; //TESTTTTT
if ($dateOutFlg)	$x_18_input .= $x_18_date['nengo'];			// 元号
$attr = array();
$attr["id"] = "x_18_1";
$attr["name"] = "x_18_1";
$attr["value"] = $x_18_date['wyear'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_18_2', event);\"";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);	// 年
if ($dateOutFlg)	$x_18_input .= "年";
$attr = array();
$attr["id"] = "x_18_2";
$attr["name"] = "x_18_2";
$attr["value"] = $x_18_date['mon'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = " onKeyPress=\"go_next_field('x_18_3', event);\"";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);	// 月
if ($dateOutFlg)	$x_18_input .= "月";
$attr = array();
$attr["id"] = "x_18_3";
$attr["name"] = "x_18_3";
$attr["value"] = $x_18_date['mday'];
$attr["maxlength"] = "2";
$attr["class"] = "textfield input-date";
$js = "";
$x_18_input .= getInputTextItem($infoEA["x_18"], $attr, $js, true);	// 日
if ($dateOutFlg)	$x_18_input .= "日\n";
else	$x_18_input .=("&nbsp;\n");
$x_18_input .= "</span>";
	
	
	
	echo"								<td colspan=\"8\" align=\"left\" nowrap>※協議事項に対して検討時間のかかる場合は、$x_18_input までに指示」するものとする。</td>\n";
	echo"							</tr>\n";
	echo"							<tr class=\"smallrow\">\n";
	echo"								<td colspan=\"8\" align=\"right\">\n";
	echo"									";
	$dateOutFlg = ($respHachuDateFlg
		 || (!$respHachuDateFlg && ($hacchusyadate['wyear'] != "")
			 && ($hacchusyadate['mon'] != "") && ($hacchusyadate['mday'] != "")));
$dateOutFlg = TRUE; //TESTTTTT
$respHachuDateFlg = TRUE; //TESTTT
	if ($dateOutFlg)	echo $hacchusyadate['nengo'];			// 元号
	$attr = array();
	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_1";
	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_1";
	$attr["value"] = $hacchusyadate['wyear'];
	$attr["maxlength"] = "2";
	$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_2', event);\"";
	echo getInputTextItem($respHachuDateFlg, $attr, $js, true);	// 年
	if ($dateOutFlg)	echo "年";
	$attr = array();
	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_2";
	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_2";
	$attr["value"] = $hacchusyadate['mon'];
	$attr["maxlength"] = "2";
	$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_3', event);\"";
	echo getInputTextItem($respHachuDateFlg, $attr, $js, true);	// 月
	if ($dateOutFlg)	echo "月";
	$attr = array();
	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_3";
	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_3";
	$attr["value"] = $hacchusyadate['mday'];
	$attr["maxlength"] = "2";
	$js = $dTxtDispCls;
	echo getInputTextItem($respHachuDateFlg, $attr, $js, true);	// 日
	if ($dateOutFlg)	echo "日";
	if (!$dateOutFlg) {
		echo "<img src=\"" . $sessionInfo->image_default_dir."spacer.gif". "\""
			. " alt=\"\" width=\"190\" height=\"1\">";
	} else if(!$respHachuDateFlg) {
		echo "<img src=\"" . $sessionInfo->image_default_dir."spacer.gif". "\""
			. " alt=\"\" width=\"80\" height=\"1\">";
	}
	echo "\n";
	echo"								</td>\n";
	echo"							</tr>\n";
	echo"						</table>\n";
	echo"					</td>\n";
	echo"				</tr>\n";

//	$chkDispCls = " class=\"g_response2\"";
//	$txtDispCls = " class=\"g_response2 editable textfield\"";
//	$dTxtDispCls = " class=\"g_response2 editable textfield input-date\"";
//	$taDispCls = " class=\"g_response2 otherta\"";
//	$resCls = "";
//	if (($hatsugisyaChk[ROLE_UKEOISHA] && !$hatsugisyaChk[ROLE_HACHUSHA])
//		 || ($lumpFlg && !$hatsugisyaChk[ROLE_UKEOISHA] && !$hatsugisyaChk[ROLE_HACHUSHA]))	$resCls = "hide";
//	echo"				<tr id=\"respContractor\" class=\"" . $resCls . "\">\n";
//	echo"					<td colspan=\"8\" class=\"r\">\n";
//	echo"						<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" summary=\"処理回答\" class=\"noneborder\">\n";
//	echo"							<tr class=\"smallrow\">\n";
//	echo"								<td class=\"nochk\" nowrap>上記事項については、</td>\n";
//	$res2ComAttr = array(
//		"name" => "response_type_2",
//	);
//	$attr = array_merge(array(
//		"id" => "response_type_2_1",
//		"value" => MINUTE_RESPONSE_CONSENT,
//	), $res2ComAttr);
//	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response2')\"";
//	$hide = array();
//	$hide["name"] = "response_type_2";
//	if ($inputData["N_RESPONSE_CATEGORY"] == ROLE_UKEOISHA) {
//		$hide["value"] = $inputData["N_RESPONSE_TYPE"];
//	} else {
//		$hide["value"] = $inputData["N_SYORI_TYPE"];
//	}
//	echo"								<td class=\"chkfirst\"><label for=\"response_type_2_1\">"
//		. getInputCheckboxItem($respUkeoiFlg, $attr, $ukeoisyoriChk[MINUTE_RESPONSE_CONSENT]
//			, $js, $hide) . "了解</label></td>\n";
//
//	$attr = array_merge(array(
//		"id" => "response_type_2_2",
//		"value" => MINUTE_RESPONSE_AGREE,
//	), $res2ComAttr);
//	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response2')\"";
//	echo"								<td><label for=\"response_type_2_2\">"
//		 . getInputCheckboxItem($respUkeoiFlg, $attr, $ukeoisyoriChk[MINUTE_RESPONSE_AGREE], $js, array())
//		 . "承諾</label></td>\n";
//
//	$attr = array_merge(array(
//		"id" => "response_type_2_3",
//		"value" => MINUTE_RESPONSE_INSTRUCT,
//	), $res2ComAttr);
//	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response2')\"";
//	echo"								<td><label for=\"response_type_2_3\">"
//		 . getInputCheckboxItem($respUkeoiFlg, $attr, $ukeoisyoriChk[MINUTE_RESPONSE_INSTRUCT], $js, array())
//		 . "後日指示</label></td>\n";
//
//	$attr = array_merge(array(
//		"id" => "response_type_2_4",
//		"value" => MINUTE_RESPONSE_ACCEPT,
//	), $res2ComAttr);
//	$js = $chkDispCls . " onClick=\"onRadioCheckboxClicked(this, 'g_response2')\"";
//	echo"								<td><label for=\"response_type_2_4\">"
//		 . getInputCheckboxItem($respUkeoiFlg, $attr, $ukeoisyoriChk[MINUTE_RESPONSE_ACCEPT], $js, array())
//		 . "受理</label></td>\n";
//
//	echo"								<td class=\"nochk\" nowrap colspan='4'>する。協議のとおり施工すること。</td>\n";
//	echo"							</tr>\n";
//	echo"							<tr class=\"largerow\">\n";
//	echo"								<td colspan=\"6\" class=\"kakko\">";
//	$attr = array();
//	$attr["id"] = "response_type_other_detail_2";
//	$attr["name"] = "response_type_other_detail_2_dum";
//	$attr["rows"] = "15";
//	$attr["cols"] = "86";
//	$js = $taDispCls;
//	if (!$respUkeoiDtFlg)	echo("<div class=\"resp_detail\">");
//	echo getTextAreaItem($respUkeoiDtFlg, $attr, $ukeoisyorisonota, $js, true);
//	if (!$respUkeoiDtFlg)	echo("</div><br>");
//	echo"</td>\n";
//	echo"							</tr>\n";
//	echo"							<tr class=\"smallrow\">\n";
//
//
//
//	echo"								<td colspan=\"8\" align=\"left\" nowrap>※協議事項に対して検討時間のかかる場合は、[] までに指示」するものとする。</td>\n";
//	echo"							</tr>\n";
//	echo"							<tr class=\"smallrow\">\n";
////	echo"								<td colspan=\"8\" align=\"right\">\n";
////	echo"									";
////	$dateOutFlg = ($respUkeoiDateFlg
////		 || (!$respUkeoiDateFlg && ($ukeoidate['wyear'] != "")
////			 && ($ukeoidate['mon'] != "") && ($ukeoidate['mday'] != "")));
////	if ($dateOutFlg)	echo $ukeoidate['nengo'];				// 元号
////	$attr = array();
////	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_2_1";
////	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_2_1";
////	$attr["value"] = $ukeoidate['wyear'];
////	$attr["maxlength"] = "2";
////	$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_2_2', event);\"";
////	echo getInputTextItem($respUkeoiDateFlg, $attr, $js, true);	// 年
////	if ($dateOutFlg)	echo "年";
////	$attr = array();
////	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_2_2";
////	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_2_2";
////	$attr["value"] = $ukeoidate['mon'];
////	$attr["maxlength"] = "2";
////	$js = $dTxtDispCls . " onKeyPress=\"go_next_field('" . PN_REPMINUTE_RESP_DATE . "_2_3', event);\"";
////	echo getInputTextItem($respUkeoiDateFlg, $attr, $js, true);	// 月
////	if ($dateOutFlg)	echo "月";
////	$attr = array();
////	$attr["id"] = PN_REPMINUTE_RESP_DATE . "_2_3";
////	$attr["name"] = PN_REPMINUTE_RESP_DATE . "_2_3";
////	$attr["value"] = $ukeoidate['mday'];
////	$attr["maxlength"] = "2";
////	$js = $dTxtDispCls;
////	echo getInputTextItem($respUkeoiDateFlg, $attr, $js, true);	// 日
////	if ($dateOutFlg)	echo "日";
////	if (!$dateOutFlg) {
////		echo "<img src=\"" . $sessionInfo->image_default_dir."spacer.gif". "\""
////			. " alt=\"\" width=\"190\" height=\"1\">";
////	} else if(!$respUkeoiDateFlg) {
////		echo "<img src=\"" . $sessionInfo->image_default_dir."spacer.gif". "\""
////			. " alt=\"\" width=\"80\" height=\"1\">";
////	}
////	echo "\n";
////	echo"								</td>\n";
////	echo"								<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "_2\" value=\"" . htmlspecialchars($ukeoidate['value'], ENT_QUOTES) . "\">\n";
//	echo"							</tr>\n";
//	echo"						</table>\n";
//	echo"					</td>\n";
//	echo"				</tr>\n";
	echo"			</table>\n";

	echo"			<table id=\"comments\">\n";
	echo"				<tr>\n";
	echo"					<td>（注）</td>\n";
	echo"					<td>２部作成し、捺印後発注者と請負者が各１部保管するものとする。</td>\n";
	echo"				</tr>\n";
	echo"				<tr>\n";
	echo"					<td>&nbsp;</td>\n";
	echo"					<td>不要な文字は、<span class=\"lineThrough\">　　　</span>で消すこと。</td>\n";
	echo"				</tr>\n";
	echo"			</table>\n";

echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_TYPE . "\" value=\"" . ROLE_UKEOISHA . "\">\n";
echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_DATE . "\" value=\"" . htmlspecialchars($hatsugidate['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_CAT . "\" value=\"" . ROLE_HACHUSHA . "\">\n";
echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "\" value=\"" . htmlspecialchars($hacchusyadate['value'], ENT_QUOTES) . "\">\n";
echo "			<input type=\"hidden\" name=\"can_be_union\" value=\"" . $canBeUnion . "\">";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_CAT . "\" value=\"" . htmlspecialchars($inputData["N_RESPONSE_CATEGORY"], ENT_QUOTES) . "\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESPONDENT . "\" value=\"" . htmlspecialchars($inputData["C_RESPONDENT"], ENT_QUOTES) . "\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_TYPE . "\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_OTEHRD . "\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_SYORI_TYPE . "\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_SYORI_OTEHRD . "\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_SYORI_DATE . "\" value=\"\">\n";
	echo"			<input type=\"hidden\" name=\"x_1" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_1_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_2" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_2_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_3" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_3_id . "\">\n";
//	echo"			<input type=\"hidden\" name=\"x_4" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_4_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_5" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_5_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_6" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_6_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_7" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_7_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_8" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_8_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_10" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_10_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_11" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_11_id . "\">\n";
	echo"			<input type=\"hidden\" name=\"x_11\" value=\"" . $x_11_date['value'] . "\">\n";
    echo"			<input type=\"hidden\" name=\"x_12" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_12_id . "\">\n";
    echo"			<input type=\"hidden\" name=\"x_12\" value=\"" . htmlspecialchars($x_12_date['value'], ENT_QUOTES) . "\">\n";
    echo"			<input type=\"hidden\" name=\"x_13" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_13_id . "\">\n";
    echo"			<input type=\"hidden\" name=\"x_13\" value=\"" . htmlspecialchars($x_13_date['value'], ENT_QUOTES) . "\">\n";
echo"			<input type=\"hidden\" name=\"x_14" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_14_id . "\">\n";
echo"			<input type=\"hidden\" name=\"x_15" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_15_id . "\">\n";
echo"			<input type=\"hidden\" name=\"x_16" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_16_id . "\">\n";
echo"			<input type=\"hidden\" name=\"x_17" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_17_id . "\">\n";
echo"			<input type=\"hidden\" name=\"x_18" . PN_REPORT_INPUT_ID_POSTFIX . "\" value=\"" . $x_18_id . "\">\n";
echo"			<input type=\"hidden\" name=\"x_18\" value=\"" . htmlspecialchars($x_18_date['value'], ENT_QUOTES) . "\">\n";

//	echo"			<input type=\"hidden\" name=\"c_response_type_other_detail\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"response_type_other_detail_2\" value=\"\">\n";
//	echo"			<input type=\"hidden\" name=\"x_5\" value=\"" . htmlspecialchars($x_11_date['value'], ENT_QUOTES) . "\">\n";
//	echo"			<input type=\"hidden\" name=\"x_6\" value=\"" . htmlspecialchars($x_12_date['value'], ENT_QUOTES) . "\">\n";
	echo"			<div id=\"routeIDs\">\n";
	echo"			</div>\n";

//JavaScript非出力指定時以外は関数定義する。
if (!$jsNoneFlg) {
	function outputPDFForm() {
		echo"			<input type=\"hidden\" name=\"" . PN_PDF_SETATTR . "\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_proponent_type_1\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_proponent_type_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_PROP_DATE . "\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_1\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_3\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_5\" value=\"\">\n";
		
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2_1\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2_3\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_initiation_type_2_5\" value=\"\">\n";
		
		echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_DETAIL . "\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_response_type_1\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_response_type_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_response_type_3\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"n_response_type_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"c_c_response_type_other_detail\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_response_type_2_1\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_response_type_2_2\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_response_type_2_3\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"n_response_type_2_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"c_response_type_other_detail\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"" . PN_REPMINUTE_RESP_DATE . "_2\" value=\"\">\n";

		echo"			<input type=\"hidden\" name=\"x_1\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_1_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_1_3\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_1_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_1_5\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_3\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_3_2\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_3_3\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"x_4\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_5\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_6\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_7\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_8\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_10\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_11\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_12\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_13\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_14\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_15\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_16\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_17\" value=\"\">\n";
		echo"			<input type=\"hidden\" name=\"x_18\" value=\"\">\n";
//		echo"			<input type=\"hidden\" name=\"x_19\" value=\"\">\n";

		global $signsInfo_pdf;
		echoSignPDFParam($signsInfo_pdf);
	}
}
?>