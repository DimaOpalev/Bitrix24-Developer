<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

CJSCore::Init(array('jquery3'));
require_once("../assets/class/ListDopuskaClass.php");

$TListDopuska = new ListDopuskaTable;

$APPLICATION->SetTitle(Loc::getMessage("LIST_DOPUSKA_BLANK_TITLE"));


Asset::getInstance()->addCss("/dlya-sotrudnikov/assets/css/list-dopuska.css");

if( isset($_GET["ACTION"]) && ($_GET["ACTION"]=="view" || $_GET["ACTION"]=="view_all") ){
    ?><style>
        #insertCompanyStructure {
            height: unset !important;
            overflow: unset !important;
        }

        .checked_line {
            background-color: #ffe000;
        }
    </style> <?
}
?>
<div id="error_output">
</div>
<form method="post">
	<table id="header_insert_table">
	</table>
	<div id="wrap_table">
		 <? 
        if($TListDopuska->isAccess()){
             echo $TListDopuska::getDocHTML();
        }
        
        ?>
	</div>
	<div>
		 <?
        if( !isset($_GET["ACTION"]) || $_GET["ACTION"]!="view" ){
            ?> <button class="ui-btn ui-btn-primary" name="matchingButton"><?=Loc::getMessage("LIST_DOPUSKA_BUTTON_SAVE")?></button>
		<?
        }
        ?> <a class="ui-btn ui-btn-sm" href="../list-dopuska/"><?=Loc::getMessage("LIST_DOPUSKA_BUTTON_BACK")?></a>
	</div>
</form>
<script>
    <?
        if( !empty($TListDopuska->getMessage()) ){
            ?>
                var $MESSAGE = <?=json_encode($TListDopuska->getMessage())?>;
            <?
        } else {
            ?>
                var $MESSAGE = [];
            <?
        }
    ?>
    
    function showParent($this){
        $ret = $("#insertCompanyStructure",$this);
        if($ret.length==1){
            $ret = false;
        } else {
            $ret = $this.parent();
            $ret.children(".element").show();
        }
        return $ret;
    }
    
    function thisShow(){
        //1 - скрываем все элементы
        //2 - показываем элементы верхнего уровня
        //3 - у выбранного элемента раскрыть чилд ноды
        $(".element input:checked").parent().children(".element").show();
        //$("#CHECK_IBLOCK_1").prop("disabled","disabled");
        //Раскрываем ветви предка вверх
        
    }
    
    $( document ).ready(function() {
        $(".element").hide();
        $(".element:eq(0)").show();
        $(".element:eq(0)").children(".element").show();

        $input_checked = $(".element input:checked");
        if($input_checked.length>0){
            do {
                $input_checked = showParent($input_checked);
            } while ($input_checked != false);
        }

        $(".INPUT_CHECK_IBLOCK").change(function(){
            thisShow();
        });

        //Проходим по таблице импорта
        var tBodyHeader = $("#t_body_header");

        $( $("th",tBodyHeader) ).each(function( index ) {
            var width = $( this ).width(); 
            $( this ).css({"width":width });
        });
        $("#header_insert_table").css({"width":tBodyHeader.width()});
        
        
        $("td", $(".hover")[0]).each(function( index ) {
        
          var width = $( this ).width(); 
        $( this ).css({"width":width });
        });
        
        $("#header_insert_table").append(tBodyHeader);

        $('.ui-btn.ui-btn-sm').hover(
            function(){
              $(this).addClass("ui-btn-primary");
            },
            function(){
              $(this).removeClass("ui-btn-primary");
        });

        //Вывод ошибок
        $.each( $MESSAGE, function( key, value ) {
            $("#"+key).addClass("error");
            $("#error_output").append("<p class='text_error'>"+value+"</p>");
        });
        
    });
</script><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>