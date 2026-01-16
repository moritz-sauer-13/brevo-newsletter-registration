<form $FormAttributes>
    <% if $Message %>
        <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% end_if %>

    <fieldset>
        <div id="Email" class="field email text">
            <label class="left" for="{$FormName}_Email">{$Fields.fieldByName('Email').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('Email')
            </div>
        </div>

        <div id="Salutation" class="field dropdown">
            <label class="left" for="{$FormName}_Salutation">{$Fields.fieldByName('Salutation').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('Salutation')
            </div>
        </div>

        <div id="FirstName" class="field text">
            <label class="left" for="{$FormName}_FirstName">{$Fields.fieldByName('FirstName').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('FirstName')
            </div>
        </div>

        <div id="LastName" class="field text">
            <label class="left" for="{$FormName}_LastName">{$Fields.fieldByName('LastName').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('LastName')
            </div>
        </div>

        <div id="Birthday" class="field date text">
            <label class="left" for="{$FormName}_Birthday">{$Fields.fieldByName('Birthday').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('Birthday')
            </div>
        </div>

        <div id="Lists" class="field optionset checkboxset">
            <label class="left">{$Fields.fieldByName('Lists').Title}</label>
            <div class="middleColumn">
                $Fields.fieldByName('Lists')
            </div>
        </div>

        $Fields.fieldByName('SecurityID')
    </fieldset>

    <div class="Actions">
        <% if $Actions %>
            <% loop $Actions %>
                $Field
            <% end_loop %>
        <% end_if %>
    </div>
</form>
