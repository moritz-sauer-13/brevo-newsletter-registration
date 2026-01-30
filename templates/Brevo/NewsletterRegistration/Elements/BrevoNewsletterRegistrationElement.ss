<div class="container">
    <div class="flex flex-wrap gap-8 lg:gap-16">
        <div class="basis-[450px] grow-1">
            <% if $Title %><h2 class="brevo-newsletter-element__title">$Title</h2><% end_if %>
            <% if $Content %>
                <div class="brevo-newsletter-element__text">$Content</div>
            <% end_if %>
        </div>
        <div class="basis-[450px] grow-1">
            $NewsletterRegistrationForm
        </div>
    </div>
</div>
