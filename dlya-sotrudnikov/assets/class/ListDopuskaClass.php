<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of listDopuskaClass
 *
 * @author opalevdg
 */
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/dlya-sotrudnikov/list-dopuska/index.php");

class ListDopuskaTable extends Main\Entity\DataManager {
    private static $MODE = "test";
    private static $CONF = array(
        "test"  => array(
            "HTML_LIST_DOPUSKA"         => 707,
            "SVYAZ_S_LISTOM_DOPUSKA"    => 708,
            "IBLOCK_ID"                 => 131,
            "ID_BIZ_POC"                => 464,//428
        ),
        "prod"  => array(
            "HTML_LIST_DOPUSKA"         => 547,
            "SVYAZ_S_LISTOM_DOPUSKA"    => 546,
            "IBLOCK_ID"                 => 109,
            "ID_BIZ_POC"                => 382,
        )
    );

    public static function getTableName()
    {
        return 't_list_dopuska';
    }

    public static function getUfId()
    {
        return 'T_LISTDOPUSKA';
    }

    public static $accessRules = array(
        "all"       => "Предоставить",
        "cancel"    => "Нет доступа",
    );

    public static $result = array();
    public static $ERROR = array();
    public static $ACTION = "";

    private static $USER_TASK_ID = 0;
    private static $CHECK_IBLOCK = 0;

    private static $POST = array();
    private static $blockAccess = array();
    private static $USER;
    private static $readonly; //Флаг только чтение (по умолчанию разрешается создавать)
    private static $MESSAGE = array(); //Информационные сообщения
    //Проверять может ( 1 - владелец; 2 - начальник; 3 - Безопасники; 4 - ИТ директор; 5 - генеральный директор )
    private static $Access;
    private static $groupAccess = array(
        "owner"         => 0,
        "chief"         => 0,
        "security"      => 0,
        "it_director"   => 0,
        "gen_director"  => 0
    );

    public static $ID = 0;
    public static $newDocument = true;
    public static $FIELD_RESULT = 0;

    private static $PARENT_ID = 0;
    private static $HISRORY_RESULT;
    private static $REF_TASK = 0; //Ссылка на задачу

    //Структура компании в виде XML
//    private static $CompanyStructure;

    //DOM doc
    public static $doc;

    public static function init(){
        GLOBAL $USER;
        self::$USER = $USER;
        self::$USER_TASK_ID = $USER->GetID();

        if( isset($_GET["ACTION"]) ){
            self::$ACTION = $_GET["ACTION"];
        }

        self::$readonly = true;
        self::$Access = false;

        self::$result = [
            0  => Loc::getMessage("LIST_DOPUSKA_STATUS_NEW"),
            10 => Loc::getMessage("LIST_DOPUSKA_STATUS_PROGRESS"),
            20 => Loc::getMessage("LIST_DOPUSKA_STATUS_DONE"),
            80 => Loc::getMessage("LIST_DOPUSKA_STATUS_CANCEL"),
            90 => Loc::getMessage("LIST_DOPUSKA_STATUS_REJECT"),
        ];

        self::$accessRules = array(
            "all"       => Loc::getMessage("LIST_DOPUSKA_ACCEPT"),
            "cancel"    => Loc::getMessage("LIST_DOPUSKA_NO_ACCESS"),
        );

        Bitrix\Main\Loader::includeModule('iblock');
        self::DirectoryAccess();

        if( isset($_GET["ID"]) ){
            //Получить запрос по ID
            self::getMatchingbyID($_GET["ID"]);
            unset(self::$POST["matchingButton"]);
        }

        self::$POST = array_merge( self::$POST, $_POST );

        if( !empty(self::$POST) ){
            self::$readonly = false; //Предполагаем, что документ можно редактировать, пока он не записан в базу
            self::postData();
            //Нажали кнопку "Согласовать"
            if( isset(self::$POST["matchingButton"]) ){
                unset(self::$POST["matchingButton"]);

                $methodField = function(){
                    $ret = true;

                    //Проверяем заполненность поля Ф.И.О.
                    if( !isset(self::$POST["FIO"]) || empty(self::$POST["FIO"]) ){
                        $ret = false;
                        self::$MESSAGE["FIO"] = Loc::getMessage("LIST_DOPUSKA_ERROR_FIO");
                    }

                    //Проверяем должность
                    if( !isset(self::$POST["Specialty"]) || empty(self::$POST["Specialty"]) ){
                        $ret = false;
                        self::$MESSAGE["Specialty"] = Loc::getMessage("LIST_DOPUSKA_ERROR_SPECIALTY");
                    }

                    //Проверка адреса
                    if( !isset(self::$POST["workPlace"]) || empty(self::$POST["workPlace"]) ){
                        $ret = false;
                        self::$MESSAGE["workPlace"] = Loc::getMessage("LIST_DOPUSKA_ERROR_WORKPLACE");
                    }

                    //Проверка номера телефона
                    if( !isset(self::$POST["employeePhoneNumber"]) || empty(self::$POST["employeePhoneNumber"]) ){
                        $ret = false;
                        self::$MESSAGE["employeePhoneNumber"] = Loc::getMessage("LIST_DOPUSKA_ERROR_EMPLOYEE_PHONE");
                    }

                    if( empty(self::$CHECK_IBLOCK) ){
                        self::$MESSAGE["insertCompanyStructure"] = Loc::getMessage("LIST_DOPUSKA_ERROR_DEPARTMENT");
                        $ret = false;
                    }

                    //Указываем, что поле комментария должно быть обязательным
                    foreach( self::$POST["user_other"] as $other_key => $other_val ){
                        if( empty($other_val) && isset(self::$POST["user_value"][$other_key]) && self::$POST["user_value"][$other_key] != "cancel" ){
                            self::$MESSAGE["row_".$other_key] = Loc::getMessage("LIST_DOPUSKA_COMMENT_IN_BLOCK")." `".self::$blockAccess["element"][$other_key]["NAME"]."` ".Loc::getMessage("MUST_BE_FILLED");
                            $ret = false;
                        }
                    }

                    return $ret;
                };

                if(self::checkRequiredFields($methodField)){
                    self::addMatchingList();
                    //Переходим на страницу со списком листов допуска
                    LocalRedirect("index.php");
                }
            }

            if( self::$ACTION == "new" && self::$ID>0 ){
                if( self::$PARENT_ID == 0 ){
                    self::$PARENT_ID = self::$ID;
                }
                self::$ID = 0;
                self::$USER_TASK_ID = $USER->GetID();
            }

            //Отправляем документ на согласование
            if( isset(self::$POST["SendMatching"]) ){
                unset(self::$POST["SendMatching"]);
                self::$HISRORY_RESULT[]=array(
                    "DATE"      => date("d.m.Y H:i:s"),
                    "CLIENT_ID" => self::$USER->GetID(),
                    "COMMENT"   => self::$POST["COMMENT"],
                );

                $data = array(
                    "HISRORY_RESULT"    => json_encode(self::$HISRORY_RESULT),
                    "RESULT"            => 10,
                );
                self::update(self::$ID, $data);

                //Создаём документ в бизнес процессе
                CModule::IncludeModule("workflow");
                CModule::IncludeModule("bizproc");
                $PROP_text = self::getTextListDopusk();

                self::domBlockAccess(true);

                $PROP[self::$CONF[self::$MODE]["SVYAZ_S_LISTOM_DOPUSKA"]] = array( self::$ID ); 
                $PROP[self::$CONF[self::$MODE]["HTML_LIST_DOPUSKA"]] = array( "VALUE" => array( "TYPE" =>"HTML","TEXT" =>self::getDocHTML() ) ); 

                $arLoadProductArray = Array(
                    "MODIFIED_BY"    => $USER->GetID(),
                    "IBLOCK_SECTION_ID" => false, 
                    "IBLOCK_ID"      => self::$CONF[self::$MODE]["IBLOCK_ID"],/* идентификтор инфоблока */
                    "NAME"           => Loc::getMessage("LIST_DOPUSKA_BIZPROC_TITLE"). " ".self::$POST["FIO"] ." ". date("d.m.Y H:i:s"),
                    "ACTIVE"         => "Y", 
                    "PREVIEW_TEXT"   => self::$POST["COMMENT"],
                    "DETAIL_TEXT"    => $PROP_text,
                    "PROPERTY_VALUES"=> $PROP,
                );

                $el = new CIBlockElement;
                $PRODUCT_ID = $el->Add($arLoadProductArray, false, true, false);

                $arErrorsTmp = array();

                $wfId = CBPDocument::StartWorkflow(
                   self::$CONF[self::$MODE]["ID_BIZ_POC"],// идентификтор бизнес процесса 131
                   array("bizproc", "CBPVirtualDocument", $PRODUCT_ID),
                   array( "TargetUser" => "user_".intval( $GLOBALS["USER"]->GetID() ) ),
                   $arErrorsTmp
                );

                //Возврат к разделу со списком листов
                LocalRedirect("index.php");
            }
        }

        $text_only = false;
        if( self::$ACTION == "view" || self::$ACTION == "view_all" ){
            $text_only = true;
        }
        self::domBlockAccess($text_only);

    }

    //Возвращает 
    public static function getBpTaskId( $WorkflowID = 0 ){
        CModule::IncludeModule("bizproc");
        $arSelectFields = array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS", "STATUS","USER_STATUS");
        $dbRecordsList = CBPTaskService::GetList(
          array("ID" => "DESC"),
          array('WORKFLOW_ID'=>$WorkflowID),
          false,
          false,
          $arSelectFields
         );
        $arRecord = $dbRecordsList->getNext();
        return $arRecord;
    }

        //Отмена листа допуска (Аннулирование)
    public static function CancelAdmissionList( $id = 0 ){
        self::getMatchingbyID( $id );
        //Заявка одобрена
        if( $FIELD_RESULT == 20 ){
            //Ищем листы допуска 
            
        }
    }

    //Получить текстовое значение листа допуска
    public static function getTextListDopusk( $max_len=38 ){
        if(!CModule::IncludeModule('iblock')) die('error'); 

        $PROP_text = "\r\n".Loc::getMessage("LIST_DOPUSKA_EMPLOYEE").": ".self::$POST["FIO"];
        if( isset(self::$POST["employeePhoneNumber"]) && !empty(self::$POST["employeePhoneNumber"]) ){
            $PROP_text .= " (".self::$POST["employeePhoneNumber"].")";
        }
        $PROP_text .= "\r\n";
        
        if( isset(self::$POST["Specialty"]) && !empty(self::$POST["Specialty"]) ){
            $PROP_text .= Loc::getMessage("LIST_DOPUSKA_SPECIALTY").": ".self::$POST["Specialty"]."\r\n";
        }

        if( isset(self::$POST["COMMENT"]) && !empty(self::$POST["COMMENT"]) ){
            $PROP_text .= self::$POST["COMMENT"]."\r\n";
        }

        $arFilter = array(
            "IBLOCK_CODE"   => "departments",
            'ACTIVE'        => 'Y',
            'ID' => self::$CHECK_IBLOCK,
        );
        $rs_section = CIBlockSection::GetList(array(), $arFilter)->Fetch();
        
        $PROP_text .= Loc::getMessage("LIST_DOPUSKA_DEPARTMENT").": ".$rs_section["NAME"]."\r\n";
        $PROP_text .= "Статус: ".self::$result[self::$FIELD_RESULT]."\r\n";
        $PROP_arr = array();
        $section = "";
//        $max_len = 38; //Количество символов в строке

        foreach( self::$POST["user_value"] as $user_key => $user_value ){

            if( $user_value != "cancel" ){

                $IBLOCK_SECTION_ID = self::$blockAccess["element"][$user_key]["IBLOCK_SECTION_ID"];

                if( self::$blockAccess["section"][$IBLOCK_SECTION_ID]["NAME"] != $section ){
                    $section = self::$blockAccess["section"][$IBLOCK_SECTION_ID]["NAME"];
                    $PROP_text .= "\r\n".$section."\r\n";
                }

                //Вывод выбранных значений
                $wrap_str = explode( " ", self::$blockAccess["element"][$user_key]["NAME"] );
                if( self::$blockAccess["element"][$user_key]["CODE"] == "other" ){
                    $wrap_str[] = "(".self::$blockAccess["element"][$user_key]["user_other"].")";
                }
                $str_str = "";

                $substr = self::$accessRules[$user_value]." - ".implode(" ", $wrap_str);

                $PROP_text .= $substr."\r\n";

            }

        }

        $PROP_text.= "\r\n"
                . Loc::getMessage("LIST_DOPUSKA_DETAILS"). ": "
                . "[url=/dlya-sotrudnikov/list-dopuska/blank-lista-dopuska.php?ID=".self::$ID."&ACTION=view]".Loc::getMessage("LIST_DOPUSKA_VIEW")."[/url].";

        return $PROP_text;
    }

    //Получить лист допуска в виде таблицы HTML
    public static function getHTMListDopusk(){
        if(!CModule::IncludeModule('iblock')) die('error'); 

        $Rem_HTML_text = array();

        $matching = array();

        $DATE_DOPUSK = new DateTime();
        $DATE_DOPUSK = $DATE_DOPUSK->format("d.m.Y");
        // Получаем предыдущие данные по листу допуска
        if( self::$PARENT_ID>0 ){

            $parameters = array(
                "filter"    => array( "ID" => self::$PARENT_ID ),
                "limit"     => 1,
            );

            $matching = self::getList($parameters)->fetch();

            if( isset($matching["POST_DATA_LIST"]) ){
                $matching = array_merge( $matching, json_decode( $matching["POST_DATA_LIST"], true ) );
                unset( $matching["POST_DATA_LIST"] );
            }

            $DATE_DOPUSK = $matching["DATE_DOPUSK"]->format("d.m.Y");
        }

        $HTML_text[] = "[p]".Loc::getMessage("LIST_DOPUSKA_EMPLOYEE").": [b]".self::$POST["FIO"]."[/b]";

        if( isset(self::$POST["employeePhoneNumber"]) && !empty(self::$POST["employeePhoneNumber"]) ){
            $HTML_text[] = " (".self::$POST["employeePhoneNumber"].")";
        }

        if( isset(self::$POST["COMMENT"]) && !empty(self::$POST["COMMENT"]) ){
            $HTML_text[] = self::$POST["COMMENT"];
        }

        $HTML_text[] = "[/p]";

        $HTML_text[] = "[p]".Loc::getMessage("LIST_DOPUSKA_SPECIALTY").": [b]".self::$POST["Specialty"]."[/b][/p]";

        $arFilter = array(
            "IBLOCK_CODE"   => "departments",
            'ACTIVE'        => 'Y',
            'ID' => self::$CHECK_IBLOCK,
        );
        $rs_section = CIBlockSection::GetList(array(), $arFilter)->Fetch();

        if(isset(self::$POST["workPlace"]) && !empty(self::$POST["workPlace"]) ){
            $HTML_text[] = "[p]".Loc::getMessage("LIST_DOPUSKA_PLACE_WORK").": [b]".self::$POST["workPlace"]."[/b][/p]";
        }
        $HTML_text[] = "[p]".Loc::getMessage("LIST_DOPUSKA_DEPARTMENT").": [b]".$rs_section["NAME"]."[/b][/p]";
        $HTML_text[] = "[p]".Loc::getMessage("LIST_DOPUSKA_TATUS").": [b]".self::$result[self::$FIELD_RESULT]."[/b][/p]";
        $section = "";

        $HTML_text[] = "[table]";
        foreach( self::$POST["user_value"] as $user_key => $user_value ){
            if( $user_value != "cancel" ){

                $IBLOCK_SECTION_ID = self::$blockAccess["element"][$user_key]["IBLOCK_SECTION_ID"];

                if( self::$blockAccess["section"][$IBLOCK_SECTION_ID]["NAME"] != $section ){
                    $section = self::$blockAccess["section"][$IBLOCK_SECTION_ID]["NAME"];
                    $HTML_text[] = ""
                    . "[tr]"
                        . "[th]"
                            . $section
                        . "[/th]"
                        . "[th]"
                        . "[/th]"
                    . "[/tr]";
                }

                $td_str = self::$blockAccess["element"][$user_key]["NAME"];
                if( self::$blockAccess["element"][$user_key]["CODE"] == "other" ){
                    $td_str .= "(".self::$blockAccess["element"][$user_key]["user_other"].")";
                }

                //Предыдущие пользовательские данные для сверки
                $prev_user_value = " ".Loc::getMessage("LIST_DOPUSKA_ALREADY_AVAILABLE")." (".$DATE_DOPUSK.")";
                if( ( isset( $matching["user_value"][$user_key] ) && $matching["user_value"][$user_key] == "cancel" || !isset( $matching["user_value"][$user_key] ) ) && $user_value != "cancel" ){
                    $prev_user_value = " [b](".Loc::getMessage("LIST_DOPUSKA_ADD").")[/b] ";
                }

                //Вывод выбранных значений
                $HTML_text[] = ""
                . "[tr]"
                        . "[td]"
                            . $td_str
                        . "[/td]"
                        . "[td]"
                            . self::$accessRules[$user_value].$prev_user_value
                        . "[/td]"
                . "[/tr]";
            } else if( isset( $matching["user_value"][$user_key] ) && $matching["user_value"][$user_key] != "cancel" ) {

                $td_str = self::$blockAccess["element"][$user_key]["NAME"];

                $Rem_HTML_text[] = ""
                . "[tr]"
                        . "[td]"
                            . $td_str
                        . "[/td]"
                        . "[td]".Loc::getMessage("LIST_DOPUSKA_ADD")."[/td]"
                . "[/tr]";
            }
        }

        if( !empty($Rem_HTML_text) ){
            $HTML_text[] = ""
            . "[tr]"
                . "[th][b]".Loc::getMessage("LIST_DOPUSKA_REMOVE_ACCESS")."[/b][/th]"
                . "[th]"
                . "[/th]"
            . "[/tr]". implode($Rem_HTML_text);

        }

        $HTML_text[] = "[/table]";
        return implode($HTML_text);
    }

    // Создаём задачу по шаблону
    public static function createTask( $task_id = 598, $TaskData = array() ){
        $user_id = self::$USER->GetId();
        $owner = array_keys(CIntranetUtils::GetDepartmentManager(array(self::$CHECK_IBLOCK), false, true));
        if( isset($owner[0]) ){
            $owner = $owner[0];
        } else {
            $owner = self::$groupAccess["owner"];
        }

        self::isAccess($owner);

        self::domBlockAccess(true);

        $overrideTaskData = array_merge( array(
            "TITLE"             => Loc::getMessage("LIST_DOPUSKA_TASK_TITLE").": ".self::$POST["FIO"]." от ".date("d.m.Y"),
            "DESCRIPTION"       => self::getHTMListDopusk(),
            "CREATED_BY"        => $user_id,
            "ALLOW_TIME_TRACKING"   => "Y",
            "ADD_IN_REPORT"     => "Y",
        ), $TaskData );

        //По просьбе Милютина, договорились задачу по листу допуска ставить от имени робота 18.02.2019
        $user_id = 608;
        $overrideTaskData["CREATED_BY"] = $user_id;

        if(!CModule::IncludeModule('tasks')) die('error');

        if( empty(self::$REF_TASK) ){
            $task_item = CTaskItem::addByTemplate(
                $task_id, 
                $user_id,
                $overrideTaskData,
                $parameters = array() 
            );

            foreach( $task_item as $task ){
                $taskID = $task->getId();

                CTasks::AddAuditors( $taskID, self::$groupAccess["owner"] );

                $arFields = Array(
                    "GROUP_ID" => 174,
                );

                $TTask = new CTasks();
                $TTask->Update($taskID, $arFields);

                $row["REF_TASK"] = $taskID;

                if( self::$PARENT_ID>0 ){
                    self::update( self::$PARENT_ID, array(
                        "PRE_RESULT"    => 20,
                        "RESULT"        => 80
                    ) );
                }

                $ret = self::update( self::$ID, $row );
            }

        }
    }

//    Проверка обязательных полей ($methodField - функция, которая проверяет на валидность)
    public static function checkRequiredFields($methodField = null){

        $ret = true;
        if( gettype($methodField) == "object" ){
            $ret = $methodField();
        }
        return $ret;
    }

    public static function domBlockAccess($text_only = false){
        self::$doc = new DOMDocument('1.0', "UTF-8");
        $insert_div_catalog = self::$doc->createDocumentFragment();
        $tr_str = "";
        if( !$text_only ){
            $tr_str = '<input type="hidden" name="PARENT_ID" id="PARENT_ID" value="'.self::$PARENT_ID.'"/>';
        }

        $div_catalog = self::$doc->createElement( "div" );

        if($insert_div_catalog->appendXML($tr_str)){
            $div_catalog->appendChild($insert_div_catalog);
        }

        self::$doc->appendChild($div_catalog);

        $list_catalog = self::$doc->createElement( "table" );
        $list_catalog_class = self::$doc->createAttribute( "class" );
        $list_catalog_class->value = "table table-striped";
        $list_catalog->appendChild($list_catalog_class);

        $list_catalog_style = self::$doc->createAttribute( "style" );
        $list_catalog_style->value = "max-width: 800px; width: 100%;";
        $list_catalog->appendChild($list_catalog_style);

        $thead = self::$doc->createElement( 'thead' );
        $thead_class = self::$doc->createAttribute( "class" );
        $thead_class->value = "center";
        $thead->appendChild( $thead_class );

        $tr = self::$doc->createElement( 'tr' );

        $thead = self::$doc->createDocumentFragment();

        $tr_str = '<thead><input type="hidden" name="ID" value="'.self::$ID.'"/></thead>'
            . '<thead class="text-center"';
        $tr_str .= ' id="t_body_header"';

        $FIO = "";
        if( isset(self::$POST["FIO"]) ){
            $FIO = self::$POST["FIO"];
        }
        $FIO_str = '<input type="text" name="FIO" id="FIO" value="'.$FIO.'" class="other"/>';

        $workPlace = "";
        if( isset(self::$POST["workPlace"]) ){
            $workPlace = htmlspecialchars( self::$POST["workPlace"] );

        }
        $workPlace_str = '<input type="text" name="workPlace" id="workPlace" value="'.$workPlace.'" class="other"/>';

        if($text_only){
            $FIO_str = '<p id="FIO">'.$FIO.'</p>';
            $workPlace_str = '<p id="workPlace">'.$workPlace.'</p>';
        }

        $Specialty = "";
        if( isset(self::$POST["Specialty"]) ){
            $Specialty = htmlspecialchars( self::$POST["Specialty"] );
        }
        $Specialty_str = '<input type="text" name="Specialty" id="Specialty" value="'.$Specialty.'" class="other"/>';
        if($text_only){
            $Specialty_str = '<p id="Specialty">'.$Specialty.'</p>';
        }

        $employeePhoneNumber = "";
        if( isset(self::$POST["employeePhoneNumber"]) ){
            $employeePhoneNumber = htmlspecialchars( self::$POST["employeePhoneNumber"] );
        }
        $employeePhoneNumber_str = '<input type="text" name="employeePhoneNumber" id="employeePhoneNumber" value="'.$employeePhoneNumber.'" class="other"/>';
        if($text_only){
            $employeePhoneNumber_str = '<p id="employeePhoneNumber">'.$employeePhoneNumber.'</p>';
        }


        $tr_str .= '>'
            . '<tr>'
                . '<td colspan="3"><label for="FIO" class="label">'.Loc::getMessage("LIST_DOPUSKA_EMPLOYEE").':</label></td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3">'.$FIO_str.'</td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3"><label for="Specialty" class="label">'.Loc::getMessage("LIST_DOPUSKA_EMPLOYEE_POSITION").':</label></td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3">'.$Specialty_str.'</td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3"><label for="employeePhoneNumber" class="label">'.Loc::getMessage("LIST_DOPUSKA_EMPLOYEE_POSITION").':</label></td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3">'.$employeePhoneNumber_str.'</td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3"><label for="workPlace" class="label">'.Loc::getMessage("LIST_DOPUSKA_PLACE_WORK").'</label></td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3">'.$workPlace_str.'</td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3"><b>'.Loc::getMessage("LIST_DOPUSKA_DEPARTMENT").':</b>'
                    . '<div id="insertCompanyStructure"></div>'
                . '</td>'
            . '</tr>'
            . '<tr>'
                . '<td colspan="3"><b>'.Loc::getMessage("LIST_DOPUSKA_TATUS").'</b>: '.self::$result[self::$FIELD_RESULT].'</td>'
            . '</tr>';
        if(!$text_only){
            $tr_str.= '<tr>'
                . '<th>'.Loc::getMessage("LIST_DOPUSKA_TYPE_ACCESS").'</th>'
                . '<th>'.Loc::getMessage("LIST_DOPUSKA_ACCEPT").'</th>'
                . '<th>'.Loc::getMessage("LIST_DOPUSKA_NO_ACCESS").'</th>'
            . '</tr>';
        }

        $tr_str .= '</thead>';

        if($thead->appendXML($tr_str)){
            $list_catalog->appendChild($thead);
        }
        $div_catalog->appendChild($list_catalog);
        $xpath = new DOMXpath(self::$doc);
        $CompanyStructure = $xpath->query("//*[@id='insertCompanyStructure']")->item(0);

        $arFilter = array(
            "IBLOCK_CODE"   => "departments",
            'ACTIVE'        => 'Y',
        );

        if(!$text_only){

            $rs_section = CIBlockSection::GetTreeList($arFilter, Array("UF_*"));

            while($ar_section = $rs_section->GetNext()) {

                //Начинаем собирать дерево компании в XML

                $checked = "";

                if(self::$CHECK_IBLOCK == $ar_section["ID"] ){
                    $checked = 'checked="checked"';
                }

                $element_str  = '<div id="IBLOCK_SECTION_ID_'.$ar_section["ID"].'" class="element">'
                    . '<input class="INPUT_CHECK_IBLOCK" id="CHECK_IBLOCK_'.$ar_section["ID"].'" type="radio" '.$checked.' value="'.$ar_section["ID"].'" name="CHECK_IBLOCK"/>'
                    . '<label for="CHECK_IBLOCK_'.$ar_section["ID"].'">'.$ar_section["NAME"].'</label>'
                . '</div>';

                $element_obj = self::$doc->createDocumentFragment();

                $group = $xpath->query("//*[@id='IBLOCK_SECTION_ID_".$ar_section["IBLOCK_SECTION_ID"]."']");

                if($element_obj->appendXML($element_str)){
                    if( $group->length>0 ){
                        $group[0]->appendChild( $element_obj );
                    } else {
                        $CompanyStructure->appendChild( $element_obj );
                    }

                }

            }
        } else {
            $arFilter['ID'] = self::$CHECK_IBLOCK;
            $rs_section = CIBlockSection::GetList(array(), $arFilter)->Fetch();

            $element_obj = self::$doc->createDocumentFragment();

            $element_str = "<p>".$rs_section["NAME"]."</p>";
            if($element_obj->appendXML($element_str)){
                $CompanyStructure->appendChild( $element_obj );
            }
        }

        foreach( self::getBlockAccess()["section"] as $ar_section ){
            $thead = self::$doc->createDocumentFragment();
            if($thead->appendXML('<thead id="section_'.$ar_section["ID"].'" class="depth_level'.$ar_section["DEPTH_LEVEL"].'">'
                . '<tr>'
                    . '<th colspan="3" class="text-center">'.$ar_section["NAME"].'</th>'
                . '</tr>'
            . '</thead>')){
                $list_catalog->appendChild($thead);
            }
        }

        foreach( self::getBlockAccess()["element"] as $ar_fields ){

            $element = $xpath->query( "//*[@id='section_".$ar_fields["IBLOCK_SECTION_ID"]."']" )->item(0);

            $other = "";
            $other_text = "";

            if(isset($ar_fields["user_other"])){
                $other_text = $ar_fields["user_other"];
            }

            if( $ar_fields["CODE"] == "other" ){
                $other = '<textarea class="other" rows="2" name="user_other['.$ar_fields["ID"].']" cols="20">'.$other_text.'</textarea>';
                if( $text_only ){
                    $other = $other_text;
                }
            }

//            foreach ($thead as $element) {
            $tr_str = "";
            if( !empty($element) ) {

                $tr = self::$doc->createDocumentFragment();
                $checked    = array(
                    "all"       => "",
                    "cancel"    => "",
                );

                if(isset($ar_fields["user_value"])){
                    $checked[$ar_fields["user_value"]] = 'checked="checked"';
                } else {
                    $checked["cancel"] = 'checked="checked"';
                }

                $CHECKED_LINE = "";
                $STYLE_BACKGROUND = "";
                if(empty($checked["cancel"])){
                    $CHECKED_LINE = "checked_line";
                    $STYLE_BACKGROUND = 'style="background-color: #fcfcad;"';
                }

                if( !$text_only ){
                $tr_str = 
                '<tr class="hover" id="row_'.$ar_fields["ID"].'">'
                    . '<td>'
                        . '<label for="user_access_'.$ar_fields["ID"].'">'.$ar_fields["NAME"].'</label>'
                        . $other
                    . '</td>'
                    . '<td class="text-center">'
                        . '<input type="radio" value="all" '.$checked["all"].' name="user_value['.$ar_fields["ID"].']" id="all'.$ar_fields["ID"].'"/>'
                    . '</td>'
                    . '<td class="text-center">'
                        . '<input type="radio" value="cancel" '.$checked["cancel"].' name="user_value['.$ar_fields["ID"].']" id="cancel'.$ar_fields["ID"].'"/>'
                    . '</td>'
                . '</tr>';
                } else {

//                    if(empty($checked["cancel"])){
                        $tr_str = 
                        '<tr class="hover '.$CHECKED_LINE.'" '.$STYLE_BACKGROUND.'>'
                            . '<td colspan="2"><div>'.$ar_fields["NAME"].'</div>'
                                . $other
                            . '</td>'
                            . '<td class="text-center">';
                            if( !empty($checked["all"]) ){
                                $tr_str.=self::$accessRules["all"];
                            }

                            if( !empty($checked["cancel"]) ){
                                $tr_str.=self::$accessRules["cancel"];
                            }
                            $tr_str.='</td>'
                        . '</tr>';
//                    }

                }

                if( !empty($tr_str) && $tr->appendXML($tr_str)){
                    $element->appendChild($tr);
                }

                if(!empty($element)){
                    $tr = $element->getElementsByTagName('tr');
                }

                if( $tr->length == 1 ){
                    $element->removeChild($tr->item(0));
                }

            }
        }
    }

    //Отдаём HTML DOM docyment
    public static function getDocHTML(){
        return self::$doc->saveHTML();
    }

    //Формируем просто массив с выбранными значениями
    public static function isAccess( $user_id = 0 ){

        self::defPrivilegedUsers($user_id);

        self::$Access = false;

        array_walk_recursive(self::$groupAccess, function( $item, $key, $user_id_){
            if( $user_id_ == 0 ){
                $user_id_ = self::$USER->GetID();
            }

            if( $item == $user_id_ ){
                self::$Access = true;
            }

        }, $user_id);

        return self::$Access;
    }

//    сеттер USER_TASK_ID
    public static function setUserTaskID($USER_TASK_ID){
        self::$USER_TASK_ID = $USER_TASK_ID;
    }

    //сеттер $CHECK_IBLOCK
    public static function setIblock($CHECK_IBLOCK){
        self::$CHECK_IBLOCK = $CHECK_IBLOCK;
    }

    //геттер $CHECK_IBLOCK
    public static function getIblock(){
        return self::$CHECK_IBLOCK;
    }

    //Определение привилегированных пользователей ($groupAccess должна содержать массив разрешённых пользователей)
    public static function defPrivilegedUsers($user_id = 0){
        self::$groupAccess["owner"] = array(self::$USER_TASK_ID); //владелец;
        
        //Ищем начальника у текущего пользователя
        //https://bitrix.center-light.ru/company/vis_structure.php
        //По структуре компании находим Отдел службы безопасности и ИТ службу
        if(CModule::IncludeModule("intranet")){
            //Ищем начальника у залогинившегося пользователя
                self::$groupAccess["chief"] = array(); //начальник;
                //Необходимо определить всех начальников, вверх по дереву, начиная с указанного в CHECK_IBLOCK
//                if(empty(self::$CHECK_IBLOCK)){
                    self::$groupAccess["chief"] = array_keys(CIntranetUtils::GetDepartmentManager(CIntranetUtils::GetUserDepartments((int)self::$groupAccess["owner"]), false, true));
//                } else {
                  
                if(!empty(self::$CHECK_IBLOCK)){
                    $IBLOCK_SECTION_ID = self::$CHECK_IBLOCK;
                    $CompanyStructure = self::listCompanyStructure();
                    $chief = array();
                    while (array_key_exists($IBLOCK_SECTION_ID, $CompanyStructure)) {
                        $chief[] = $IBLOCK_SECTION_ID;
                        $IBLOCK_SECTION_ID = $CompanyStructure[$IBLOCK_SECTION_ID]["IBLOCK_SECTION_ID"];
                    }
                    if(!empty($IBLOCK_SECTION_ID)){
                        $chief[] = $IBLOCK_SECTION_ID;
                    }
                    self::$groupAccess["chief"] = array_keys(CIntranetUtils::GetDepartmentManager($chief, false, true));
                }
                
            if(empty(self::$groupAccess["security"])){
                self::$groupAccess["security"]      = array(); //Безопасники;
                //Поиск сотрудников в отделе безопасности
                //https://dev.1c-bitrix.ru/support/forum/forum23/topic64897/
                $UF_DEPARTMENT = 128; //Отдел службы безопасности
                $security = CIntranetUtils::GetDepartmentEmployees($UF_DEPARTMENT, $bRecursive = false, $bSkipSelf = false, $onlyActive = 'Y');
                while( $arSecurity = $security->GetNext() ){
                    if( isset($arSecurity["ID"]) && !empty($arSecurity["ID"]) ){
                        self::$groupAccess["security"][] = $arSecurity["ID"];
                    }
                }
            }
            
            if(empty(self::$groupAccess["it_director"])){
                self::$groupAccess["it_director"]   = array(); //ИТ директор
                //Ищем ИТ директора
                $UF_DEPARTMENT = 251; //ИТ служба
                self::$groupAccess["it_director"] = array_keys(CIntranetUtils::GetDepartmentManager(array($UF_DEPARTMENT), false, true));
            }

            if(empty(self::$groupAccess["gen_director"])){
                self::$groupAccess["gen_director"]  = array(); //генеральный директор
                //Завершающий этап, ищем генерального директора
                $UF_DEPARTMENT = 1; // Группа компаний "Лайт"
                $security = CIntranetUtils::GetDepartmentEmployees($UF_DEPARTMENT, $bRecursive = false, $bSkipSelf = false, $onlyActive = 'Y');
                while( $arSecurity = $security->GetNext() ){
                    if( isset($arSecurity["ID"]) && !empty($arSecurity["ID"]) ){
                        self::$groupAccess["gen_director"][] = $arSecurity["ID"];
                    }
                }
            }
        }
        
    }
    
//    получить список $groupAccess
    public static function getGroupAccess(){
        return self::$groupAccess;
    }
    
//    Сеттер
    public static function setGroupAccess($groupAccess = array()){
        foreach(array_keys(self::$groupAccess) as $key){
            if( isset($groupAccess[$key]) ){
                self::$groupAccess[$key] =  $groupAccess[$key];
            }
        }
    }
//    
    //Получить запрос по ID
    public static function getMatchingbyID($ID = 0){
        //Необходимо проверить разрешение просмотра 
        //Проверять может ( 1 - владелец; 2 - начальник; 3 - Безопасники; 4 - ИТ директор; 5 - генеральный директор )
        $parameters = array(
            "filter"    => array("ID" => $ID),
            "limit"     => 1,
        );
        
        $matching = self::getList($parameters)->fetch();
        
        self::$USER_TASK_ID = $matching["USER_TASK_ID"];
        self::$groupAccess["owner"] = $matching["USER_TASK_ID"];
        self::$groupAccess["chief"] = $matching["CHECK_IBLOCK"];
        self::$CHECK_IBLOCK = $matching["CHECK_IBLOCK"];
        self::$REF_TASK = $matching["REF_TASK"];

//        if( self::isAccess() ){
            
            self::$PARENT_ID = $matching["PARENT_ID"];
            //Пока нужно отключить - это потребуется при создании нового листа допуска
            /*
            if( self::$PARENT_ID == 0 ){
                self::$PARENT_ID = $matching["ID"];
            }
            */
            
            if( isset($matching["POST_DATA_LIST"]) ){
                try {
                    self::$POST = json_decode($matching["POST_DATA_LIST"], true);
                } catch (Exception $e) {
                    self::$ERROR["POST_DATA_LIST"][] = Loc::getMessage("LIST_DOPUSKA_BLANK_TITLE")."\r\n".$e->getMessage();
                }
                self::postData();
            }
            
            self::$FIELD_RESULT = $matching["RESULT"];
            self::$ID = $matching["ID"];
            
            try {
                self::$HISRORY_RESULT = json_decode($matching["HISRORY_RESULT"], true);
            } catch (Exception $e) {
                self::$ERROR["HISRORY_RESULT"][] = Loc::getMessage("LIST_DOPUSKA_ERROR_POST_DATA_LIST")."\r\n".$e->getMessage();
            }
            
            if( $matching["RESULT"] == 0 ){
                
            }
//        }
        
            //Тут вообще велосипед. Завёл массив, в котором указал идентификаторы занесённых пользователей
            $arr_val = array_values(self::$groupAccess);
            $access_user = array(1);
            if( in_array(self::$USER->GetID(), $access_user) && !in_array(self::$USER->GetID(), $arr_val)) {
                self::$groupAccess["owner"] = self::$USER->GetID();
            }
    }
    
    //Получить флаг доступности документа
    public static function getReadStatus(){
        return self::$readonly;
    }


    //Разносим POST, или просто данные по полям формы
    public static function postData(){
        
        if( isset(self::$POST["user_value"]) ){

            foreach( self::$POST["user_value"] as $user_key => $user_value ){
                if( isset(self::$blockAccess["element"][$user_key]) ){
                    self::$blockAccess["element"][$user_key]["user_value"] = $user_value;
                }
            }
        }

        if( isset(self::$POST["user_other"]) ){  
            foreach( self::$POST["user_other"] as $other_key => $other_value ){
                if( isset(self::$blockAccess["element"][$other_key]) ){
                    self::$blockAccess["element"][$other_key]["user_other"] = $other_value;
                }
            }
        }
        
        if( isset(self::$POST["ID"]) ){
            self::$ID = self::$POST["ID"]; 
            unset(self::$POST["ID"]);
        }
        
        if( isset(self::$POST["PARENT_ID"]) ){
            self::$PARENT_ID = self::$POST["PARENT_ID"]; 
            unset(self::$POST["PARENT_ID"]);
        }
        
        if( isset(self::$POST["CHECK_IBLOCK"]) ){
            self::$CHECK_IBLOCK = self::$POST["CHECK_IBLOCK"]; 
            unset(self::$POST["CHECK_IBLOCK"]);
        }
        
        if( isset(self::$POST["FIELD_RESULT"]) ){
            self::$FIELD_RESULT = self::$POST["FIELD_RESULT"];
            unset(self::$POST["FIELD_RESULT"]);
        }

    }
    
    //Обработка события нажатия кнопки "Согласовать"
    public static function addMatchingList(){
        //Сохраняем в базу данные формы
        //$USER_TASK_ID - Идентификатор пользователя, инициировавшего запись листа допуска,
        //$USER_ID - Идентификатор пользователя, кому даётся доступ.
        //POST_DATA_LIST - Данные листа допуска. (пришли через POST запрос. формат Json)
        $USER_TASK_ID = self::$USER->GetID(); 

        $ret = false;
        if( $USER_TASK_ID>0 ){
            $row = array(
//                "DATE_DOPUSK"       => \Bitrix\Main\Type\Date::createFromText(date("d.m.Y H:i:s")),
                "CHECK_IBLOCK"      => self::$CHECK_IBLOCK,
                "PARENT_ID"         => self::$PARENT_ID,
                "USER_TASK_ID"      => $USER_TASK_ID,
                "POST_DATA_LIST"    => json_encode(self::$POST),
            );
            
            try {
                if( self::$ID == 0 ){
                    $ret = self::add( $row );
                }
                
                if( self::$ID > 0 ){
                    $ret = self::update( self::$ID, $row );
                }
                
                self::$readonly = true;
            }
            catch (Exception $e) {
                self::$ERROR["addMatching"][] = $e->getMessage();
                self::$readonly = false;    //Даём возможность редактировать документ
            }
        }
        return $ret;
    }

    //геттер $POST
    public static function getPost(){
        return self::$POST;
    }
    
    //Получить Структура компании
    public static function listCompanyStructure(){
        $arFilter = array(
            "IBLOCK_CODE"   => "departments",
            'ACTIVE'        => 'Y',
        );

        $rs_section = CIBlockSection::GetTreeList($arFilter, Array("UF_*"));
        $ret = array();
        while($ar_section = $rs_section->GetNext()) {
            $ret[$ar_section["ID"]] = array(
                "IBLOCK_SECTION_ID" => $ar_section["IBLOCK_SECTION_ID"],
                "NAME"              => $ar_section["NAME"],
            );
        }
        return $ret;
    }
    
    //Параметр, содержащий в себе справочник листа Допуска
    private static function DirectoryAccess(){
        
        self::$blockAccess = array();
        
        $arFilter = array(
            "IBLOCK_CODE"   => "list_dopuska",
            'ACTIVE'        => 'Y',
            
        );
        
        $rs_section = CIBlockSection::GetTreeList( $arFilter, Array("UF_*") );
        while($ar_section = $rs_section->GetNext()) {
            self::$blockAccess["section"][$ar_section["ID"]] = $ar_section;
        }
        
        $arFilter = array(
            "IBLOCK_SECTION_ID" => array_keys(self::$blockAccess["section"]),
            "ACTIVE_DATE"       => "Y",
            "ACTIVE"            => "Y"
        );
        
        $rs_element = CIBlockElement::GetList( Array("SORT"=>"ASC"), $arFilter, false, false );
        while($ar_fields = $rs_element->getNext()) {
            self::$blockAccess["element"][$ar_fields["ID"]] = $ar_fields;
        }
        
    }
    
    //Получить значения $blockAccess
    public static function getBlockAccess(){
        return self::$blockAccess;
    }
    
    //Получить список информационных сообщений
    public static function getMessage(){
        return self::$MESSAGE;
    }


    //Описание работы запросов
    // https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=5753
    public static function getMap() {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary'   => true,
                'autocomplete' => true,
            ),
            "DATE_DOPUSK"   => array(
                'data_type'     => 'datetime',
                'default_value' => \Bitrix\Main\Type\Date::createFromText(date("d.m.Y H:i:s")),
            ),
            "PARENT_ID"   => array(
                'data_type' => 'integer',
                'required'  => true,
            ),
            "USER_TASK_ID" => array(
                'data_type' => 'integer',
                'required'  => true,
            ),
            "CHECK_IBLOCK" => array(
                'data_type' => 'integer',
                'required'  => true,
            ),
            "POST_DATA_LIST" => array(
                'data_type' => 'string',
                'required'  => true,
            ),
            "HISRORY_RESULT" => array(
                'data_type' => 'string'
            ),
            "REF_TASK" => array(
                'data_type' => 'integer'
            ),
            "RESULT" => array(
                'data_type' => 'integer'
            ),
        );
    }
}

//Инициализация класса
ListDopuskaTable::init();