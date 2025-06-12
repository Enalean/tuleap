<div id="masschange-page">
    <h3>{{ masschange_info_title }}</h3>

    <div class="masschange_artifact_ids">
        {{ changing_items }}

        {{# masschange_aids }}
            <a href="{{ tracker_base_url }}/?aid={{ . }}">#{{ . }} </a>
        {{/ masschange_aids }}
    </div>

    <form id="masschange_form" enctype="multipart/form-data" action="" method="POST">

        <input type="hidden" name="func" value="update-masschange-aids">
        {{# masschange_aids }}
            <input type="hidden" name="masschange_aids[]" value="{{ . }}" />
        {{/ masschange_aids }}
        {{# csrf_token }}
            {{> csrf_token_input }}
        {{/ csrf_token }}

        <div class="masschange-notifications">
            <label class="checkbox" for="masschange-unsubscribe-option">
                {{ unsubscribe_label }}
                <input type="checkbox" id ="masschange-unsubscribe-option" name="masschange-unsubscribe-option" value="unsubscribe" />
            </label>

            <label class="checkbox" for="masschange-notify-option">
                {{ notification_label }}
                <input type="checkbox" id="masschange-notify-option" name="notify" value="ok" />
            </label>
        </div>

        {{# has_external_actions }}
        <div class="masschange-external-actions">
            {{# external_actions }}
                {{{ . }}}
            {{/ external_actions }}
        </div>
        {{/ has_external_actions }}

        <h3>{{ artifact_fields_title }}</h3>

            {{{ form_elements }}}

        <h3>{{ add_comment }}</h3>

        <div class="masschange-followup-comment">
            <textarea
              wrap="soft"
              rows="12"
              cols="80"
              name="artifact_masschange_followup_comment"
              id="artifact_masschange_followup_comment"
              data-test="masschange-new-comment"
              data-project-id="{{ project_id }}"
            >{{ default_comment }}</textarea>
            {{# has_notifications }}
            <p class="text-info">{{# dgettext }}tuleap-tracker | When you use @ to mention someone, they will get an email notification.{{/ dgettext }}</p>
            {{/ has_notifications }}
            {{^ has_notifications }}
            <p class="text-warning">{{# dgettext }}tuleap-tracker | This tracker's notifications are disabled, when you use @ to mention someone, no email will be sent.{{/dgettext}}</p>
            {{/ has_notifications }}
        </div>
        <br />

        <input class="btn btn-primary masschange-submit" data-test="masschange-submit" type="submit" value="{{ masschange_submit }}"/>
    </form>

    {{{ javascript_rules }}}
</div>
