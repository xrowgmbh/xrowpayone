<script src="https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js"></script>

{* CONFIGURATION START *}

{def $payone_mode = ezini( 'GeneralSettings', 'Mode', 'xrowpayone.ini')
     $payone_key = ezini( 'GeneralSettings', 'Key', 'xrowpayone.ini')
     $mid = ezini( 'GeneralSettings', 'MID', 'xrowpayone.ini')
     $aid = ezini( 'GeneralSettings', 'AID', 'xrowpayone.ini')
     $algorithm = ezini( 'GeneralSettings', 'Algorithm', 'xrowpayone.ini')
     $portal_id = ezini( 'GeneralSettings', 'PortalID', 'xrowpayone.ini')
     $encoding = ezini( 'GeneralSettings', 'Encoding', 'xrowpayone.ini')
     $error_node = ezini( 'GeneralSettings', 'CustomErrorNode', 'xrowpayone.ini')
     $request_type = "creditcardcheck"
     $response_type = "JSON"
     $storecarddata = "yes"
     $api_version = ezini( 'GeneralSettings', 'APIVersion', 'xrowpayone.ini')
     $cc_hash_array = hash("aid", $aid, "encoding", $encoding, "mid", $mid, "mode", $payone_mode, "portalid", $portal_id, "request", $request_type, "responsetype", $response_type, "storecarddata", $storecarddata, "api_version", $api_version )
}

{* CONFIGURATION END *}

<div class="shop shop-payment shop-payment-gateway">
    {include uri="design:shop/basket_navigator.tpl" step='4'}
    <h1>{"Payment Information"|i18n("extension/xrowpayone")}</h1>

    <div class="form">
        <h4>{"Please enter your credit card details"|i18n("extension/xrowpayone")}</h4>

        {if $error_node|eq("disabled")}
            <div id="errorOutput" class="warning" style="display: none;"></div>
        {elseif $errors|count|gt(0)}
            <div class="warning">
                <h2>{'Validation error'|i18n('extension/xrowpayone')}</h2>
                <ul>
                    {foreach $errors as $error}
                        <li>{$error|wash()}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        <form id="paymentform" name="paymentform" action="" method="post">
            <input type="hidden" name="pseudocardpan" id="pseudocardpan" />
            <input type="hidden" name="truncatedcardpan" id="truncatedcardpan" />

            <table summary="{"Please enter your credit card details"|i18n("extension/xrowpayone")}">
                <tr>
                    <td><label for="firstname">{"Firstname"|i18n("extension/xrowpayone")}:</label></td>
                    <td><input id="firstname" type="text" name="firstname" value="" size="32"></td>
                </tr>
                <tr>
                    <td><label for="lastname">{"Lastname"|i18n("extension/xrowpayone")}:</label></td>
                    <td><input id="lastname" type="text" name="lastname" value="" size="32"></td>
                </tr>
                <tr>
                    <td><label for="cardtypeInput">{"Credit Card Type"|i18n("extension/xrowpayone")}:</label></td>
                    <td>
                        <select id="cardtype">
                            <option value="V">Visa</option>
                            <option value="M">MasterCard</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="cardpanInput">{"Card Number"|i18n("extension/xrowpayone")}:</label></td>
                    <td><span class="inputIframe" id="cardpan"></span></td>
                </tr>
                <tr>
                    <td><label for="cardcvc2">{"Security Code"|i18n("extension/xrowpayone")}:</label></td>
                    <td>
                        <span id="cardcvc2" class="inputIframe"></span>
                    </td>
                </tr>
                <tr>
                    <td><label for="expireInput">{"Expiry Date"|i18n("extension/xrowpayone")}:</label></td>
                    <td>
                        <span id="expireInput" class="inputIframe">
                            <span id="cardexpiremonth"></span>
                            <span id="cardexpireyear"></span>
                        </span>
                    </td>
                </tr>
            </table>
            {*<input id="paymentsubmit" type="button" value="Submit" onclick="check();">*}
        </form>
    </div>
    <form id="checkout_cart" name="form" action={"/shop/checkout"|ezurl()} method="post">
        <input type="submit" class="hide" style="display: hide;" name="validate" value="{'Continue'|i18n('extension/xrowpayone')}" />

        {include uri="design:workflow/order.tpl"}

        <div id="buttonblock-bottom" class="buttonblock">
            <input id="cancel-button" class="button" type="submit" name="CancelButton" value="{'One step back'|i18n('extension/xrowpayone')}" />
            {*<input id="continue-button" class="defaultbutton" type="submit" name="validate" value="{'Send Order'|i18n('extension/xrowpayone')}" />*}
            <input onclick="check();" id="continue-button" class="defaultbutton" type="button" name="validate" value="{'Send Order'|i18n('extension/xrowpayone')}" />
            <div class="break"></div>
        </div>
    </form>

</div>


{literal}
<script>
var request, config;
config = {
    fields: {
        cardpan: {
        selector: "cardpan", // put name of your div-container here
        type: "text" // text (default), password, tel
    },
    cardcvc2: {
        selector: "cardcvc2", // put name of your div-container here
        type: "password", // select(default), text, password, tel
        size: "4",
        maxlength: "4",
        iframe: {
            width: "60px"
        },
        style: "width: 60px height 22px; border: 1px solid #369;"
    },
    cardexpiremonth: {
        selector: "cardexpiremonth", // put name of your div-container here
        type: "select", // select(default), text, password, tel
        size: "2",
        maxlength: "2",
        iframe: {
            width: "50px"
        }
    },
    cardexpireyear: {
        selector: "cardexpireyear", // put name of your div-container here
        type: "select", // select(default), text, password, tel
        iframe: {
            width: "80px"
        }
    }
    },
    defaultStyle: {
        input: "font-size: 1em; border: 1px solid #369; width: 253px; height: 22px;",
        select: "font-size: 1em; border: 1px solid #369; height: 22px;",
        iframe: {
        height: "22px",
        width: "253px"
        }
    },
    error: "errorOutput", // area to display error-messages (optional)
    language: Payone.ClientApi.Language.de // Language to display error-messages
    // (default: Payone.ClientApi.Language.en)
};

request = {
    request: '{/literal}{$request_type}{literal}', // fixed value
    responsetype: '{/literal}{$response_type}{literal}', // fixed value
    mode: '{/literal}{$payone_mode}{literal}', // desired mode
    mid: '{/literal}{$mid}{literal}', // your MID
    aid: '{/literal}{$aid}{literal}', // your AID
    portalid: '{/literal}{$portal_id}{literal}', // your PortalId
    encoding: '{/literal}{$encoding}{literal}', // desired encoding
    storecarddata: '{/literal}{$storecarddata}{literal}', // fixed value
    //key: '{/literal}{$payone_key}{literal}', //PMI Portal key
    api_version: '{/literal}{$api_version}{literal}', //3.9 New API-version from 2015-01-05
    hash: '{/literal}{hashcreate( $algorithm, $cc_hash_array, $payone_key )}{literal}'
};

var iframes = new Payone.ClientApi.HostedIFrames(config, request);
iframes.setCardType("V");
document.getElementById('cardtype').onchange = function () {
    iframes.setCardType(this.value); // on change: set new type of credit card to process
};

function check() { // Function called by submitting PAY-button
    if (iframes.isComplete()) {
        iframes.creditCardCheck('checkCallback');
        // Perform "CreditCardCheck" to create and get a
        // PseudoCardPan; then call your function "payCallback"
    } else {
        //TODO hier muss eine fehlermeldung kommen da z.B. auch bei altem cc valdation er hier landet. vielleicht kommt auch schon ne meldung, ist aber ausgeblendet
        console.debug("not complete");
    }
}

function checkCallback(response) {
    console.debug(response);
    if (response.status === "VALID") {
        document.getElementById("pseudocardpan").value = response.pseudocardpan;
        document.getElementById("truncatedcardpan").value = response.truncatedcardpan;
        document.paymentform.submit();
    }
    else
    {
        $('#errorOutput').prepend('<h2>{/literal}{'Validation error'|i18n('extension/xrowpayone')}{literal}</h2>').show();
    }
}
</script>
{/literal}