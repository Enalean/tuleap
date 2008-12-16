function toggle_addurlform() {
    Effect.toggle('hudson_add_job', 'slide');
}

function toggle_iframe(joburl) {
    if ( ! $('hudson_iframe_div').visible() ) {
        Effect.toggle('hudson_iframe_div', 'appear');
    }
    $('hudson_iframe').src = joburl;
}