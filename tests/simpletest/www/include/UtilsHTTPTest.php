<?php



class UtilsHTTPTest extends TuleapTestCase {

    public function itExtractBody() {
        require_once 'utils.php';

        $string = "Content-type: sdfsdf\r\n\r\nThe body";
        list($headers, $body) = http_split_header_body($string);

        $this->assertEqual("The body", $body);
    }

    public function itExtractBodyThatStartsWithNul() {
        require_once 'utils.php';

        $string = "Content-type: sdfsdf\r\n\r\n".(0x00)."The body";
        list($headers, $body) = http_split_header_body($string);

        $this->assertEqual((0x00)."The body", $body);
    }

    public function itExtractBodyThatStartsWithLN() {
        require_once 'utils.php';

        list($headers, $body) = http_split_header_body("Content-type: sdfsdf\r\n\r\n
The body");
        $this->assertEqual("\nThe body", $body);
    }

    public function itExtractHeaders() {
        require_once 'utils.php';

        list($headers, $body) = http_split_header_body("Content-disposition: anefe
Content-type: sdfsdf\r\n\r\nThe body");
        $this->assertEqual("Content-disposition: anefe\nContent-type: sdfsdf", $headers);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=5604&group_id=101 ViewVC download broken when file start with 0x00
     */
    public function itExtractsBodyWithBinaryData() {
        require_once 'utils.php';

        list($headers, $body) = http_split_header_body(file_get_contents(dirname(__FILE__).'/_fixtures/svn_bin_data'));
        $this->assertEqual("Content-Type: text/plain", $headers);
    }
}