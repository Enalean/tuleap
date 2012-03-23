<?php
//TODO: Copy/paste disclaimer

/**
 * TODO: Class comment
 */
class Git_Widget_UserPushes extends Widget {

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        $this->Widget('plugin_git_user_pushes');
    }

    /**
     * Get the title of the widget.
     *
     * @return string
     */
    public function getTitle() {
        return 'My last Git pushes';
    }

    /**
     * Compute the content of the widget
     *
     * @return string html
     */
    public function getContent() {
        return 'blah';
    }

    /**
     * The category of the widget is scm
     *
     * @return string
     */
    function getCategory() {
        return 'scm';
    }

    /**
     * Display widget's description
     *
     * @return String
     */
    function getDescription() {
        return 'Display last pushes performed by the user';
    }

}
?>
