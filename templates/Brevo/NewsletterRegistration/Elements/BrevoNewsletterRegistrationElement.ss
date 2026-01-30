<div class="brevo-newsletter-element">
    <div class="brevo-newsletter-element__wrapper">
        <div class="brevo-newsletter-element__content">
            <% if $Title %><h2 class="brevo-newsletter-element__title">$Title</h2><% end_if %>
            <% if $Content %>
                <div class="brevo-newsletter-element__text">$Content</div>
            <% end_if %>
        </div>
        <div class="brevo-newsletter-element__form">
            $NewsletterRegistrationForm
        </div>
    </div>
</div>
