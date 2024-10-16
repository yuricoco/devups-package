<?php
define('IN_CB', true);
require __DIR__ . '/../vendor/autoload.php';
include('include/header.php');

$default_value['start'] = 'C';
$start = isset($_POST['start']) ? $_POST['start'] : $default_value['start'];
registerImageKey('start', $start);

$identifiers = new stdClass;
$identifiers->{''} = 'Select an identifier';

if (class_exists('BarcodeBakery\Common\GS1\GS1AI')) {
    foreach (BarcodeBakery\Common\GS1\GS1AI::getDefaultAIData() as $aiData) {
        $identifiers->{$aiData->getAI()} = $aiData->getAI() . ' - ' . $aiData->getDescription();
    }
} else {
    $identifiers->{'0'} = 'Load the package barcode-bakery/gs1ai to populate this list.';
}

registerImageKey('code', 'BCGgs1128');

$vals = array();
for ($i = 0; $i <= 127; $i++) {
    $vals[] = '%' . sprintf('%02X', $i);
}
$characters = array(
    'NUL', 'SOH', 'STX', 'ETX', 'EOT', 'ENQ', 'ACK', 'BEL', 'BS', 'TAB', 'LF', 'VT', 'FF', 'CR', 'SO', 'SI', 'DLE', 'DC1', 'DC2', 'DC3', 'DC4', 'NAK', 'SYN', 'ETB', 'CAN', 'EM', 'SUB', 'ESC', 'FS', 'GS', 'RS', 'US',
    '&nbsp;', '!', '"', '#', '$', '%', '&', '\'', '(', ')', '*', '+', ',', '-', '.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
    '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[', '\\', ']', '^', '_',
    '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~', 'DEL'
);
?>

<ul id="specificOptions">
    <li class="option">
        <div class="title">
            <label for="start">Starts with</label>
        </div>
        <div class="value">
            <?php echo getSelectHtml('start', $start, array('NULL' => 'Auto', 'A' => 'Code 128-A', 'B' => 'Code 128-B', 'C' => 'Code 128-C')); ?>
        </div>
    </li>
    <li class="option">
        <div class="title">
            <label for="identifier">Identifiers</label>
        </div>
        <div class="value">
            <?php echo getSelectHtml('identifier', null, $identifiers, array('style' => 'width: 100%')); ?>
            <div id="identifierContainer"></div>
        </div>
    </li>
</ul>

<div id="validCharacters">
    <h3>Valid Characters</h3>
    <?php $c = count($characters); for ($i = 0; $i < $c; $i++) {
    echo getButton($characters[$i], $vals[$i]);
} ?>
</div>

<div id="explanation">
    <h3>Explanation</h3>
    <ul>
        <li>Encoded as Code 128.</li>
        <li>The former correct name was UCC/EAN-128.</li>
        <li>Used for shipping containers.</li>
        <li>Based on the GS1 standard.</li>
    </ul>
</div>

<script>
(function($) {
    "use strict";

    var identifierSelect = $("#identifier"),
        identifierContainer = $("#identifierContainer"),
        generateText = $("#text");

    var updateText = function() {
        var text = "";
        $(".gs1128_identifier").each(function() {
            var $this = $(this);
            text += "(" + $this.find(".gs1128_id").val() + ")" + $this.find(".gs1128_value").val() + "~F1";
        });
        text = text.substring(0, text.length - 3);
        generateText.val(text);
    };

    var addIdentifier = function(id) {
        var identifier = $("<div class='gs1128_identifier'><input type='text' value='" + id + "' class='gs1128_id' readonly='readonly' /> - <input type='text' class='gs1128_value' /><a href='#' class='gs1128_delete'><img src='delete.png' alt='Delete' /></a></div>")
            .appendTo(identifierContainer)

        identifier.find(".gs1128_delete").on("click", function() {
            $(this).closest(".gs1128_identifier").remove();
            updateText();
            return false;
        });
        identifier.find(".gs1128_value").on("keyup", function() {
            updateText();
        });

        identifierSelect.val();
        return;
    };

    identifierSelect.change(function() {
        addIdentifier($(this).find("option:selected").val());
        updateText();
    });

    generateText.on("keyup", function() {
        var val = $(this).val(),
            section = val.split("~F1"),
            i = 0, regex = /^\(([0-9]*y?)\)(.*)$/,
            result;

        // Let's remove all identifiers we put already
        $(".gs1128_identifier").remove();
        for (i = 0; i < section.length; i++) {
            // we are able to handle only if you have ()
            result = regex.exec(section[i]);
            if (result.length === 3) {
                addIdentifier(result[1]);
                $(".gs1128_identifier").eq(i).find(".gs1128_value").val(result[2]);
            } else {
                // Oups, you entered something wrong...
                $(".gs1128_identifier").remove();
                break;
            }
        }
    });

    $(function() {
        if (generateText.val() !== "") {
            generateText.keyup();
        }
    });
})(jQuery);
</script>

<?php
include('include/footer.php');
?>
