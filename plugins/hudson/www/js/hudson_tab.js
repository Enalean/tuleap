function toggle_addurlform() {
    Effect.toggle('hudson_add_job', 'slide', { duration: 0.3 });
}

function toggle_iframe(joburl) {
    if ( ! $('hudson_iframe_div').visible() ) {
        Effect.toggle('hudson_iframe_div', 'appear', { duration: 0.3 });
    }
    $('hudson_iframe').src = joburl;
    $('link_show_only').href = joburl;
}