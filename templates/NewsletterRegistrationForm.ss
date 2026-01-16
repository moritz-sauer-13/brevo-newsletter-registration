<form $FormAttributes>
    <% if $Message %>
        <p id="{$FormName}_error" class="message $MessageType">$Message</p>
    <% end_if %>

    <fieldset>
        $Fields.fieldByName('Salutation').FieldHolder
        $Fields.fieldByName('FirstName').FieldHolder
        $Fields.fieldByName('LastName').FieldHolder
        $Fields.fieldByName('Email').FieldHolder

        $Fields.fieldByName('Birthday').FieldHolder

        $Fields.fieldByName('Lists').FieldHolder

        $Fields.fieldByName('SecurityID')
        <div class="mb-4">
            $Fields.fieldByName('Captcha')
        </div>
    </fieldset>

    <div class="Actions">
        <% if $Actions %>
            <% loop $Actions %>
                $Field
            <% end_loop %>
        <% end_if %>
    </div>
</form>
