<?php
// user \dclass\devups\model\Model;
use PHPMailer\PHPMailer\PHPMailer;

class DPdf
{

    public $title = PROJECT_NAME . "";
    public $object;
    public $content;
    public $lang;
    public $receivers = [];
    public $receiver_cc = [];
    public $attachments = [];

    public static $view;

    private $margin_left = 0;
    private $margin_right = 0;
    private $margin_top = 0;
    private $margin_bottom = 0;
    private $orientation = 'P';
    private $configs = [];
    private $fontDirs = [];
    private $fontData = [];

    public function __construct()
    {
        $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $this->fontDirs = $defaultConfig['fontDir'];
//        $this->fontDirs = [];

        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $this->fontData = $defaultFontConfig['fontdata'];
//        $this->fontData = [];

        $this->configs['fontDir'] = array_merge($this->fontDirs, [
            ROOT . 'web/assets/fonts',
        ]);

    }
    /**
     * @param $model
     * @return Reportingmodel
     */
    public static function init($view, $data)
    {

        self::$view = Genesis::getView($view, $data);
        return new \DPdf();

    }

    /**
     * Values:
    \Mpdf\Output\Destination::INLINE, or "I"
    send the file inline to the browser. The plug-in is used if available. The name given by $filename is used when one selects the “Save as” option on the link generating the PDF.
    \Mpdf\Output\Destination::DOWNLOAD, or "D"
    send to the browser and force a file download with the name given by $filename.
    \Mpdf\Output\Destination::FILE, or "F"
    save to a local file with the name given by $filename (may include a path).
    \Mpdf\Output\Destination::STRING_RETURN, or "S"
    return the document as a string. $filename is ignored.
     *
     * https://mpdf.github.io/reference/mpdf-functions/output.html
     *
     * @param string $name
     * @param string $dest
     * @return string
     * @throws \Mpdf\MpdfException
     */
    public function render($name = '', $dest = 'I')
    {

        $mpdf = new \Mpdf\Mpdf([
            "margin_left" => $this->margin_left,
            "margin_right" => $this->margin_right,
            "margin_top" => $this->margin_top,
            "margin_bottom" => $this->margin_bottom,
            "orientation" => $this->orientation,
        ]+$this->configs);

// Write some HTML code:
        $mpdf->WriteHTML(self::$view);
        if ($name && $dest != 'S') {
            $name = UPLOAD_DIR . "$name.pdf";
// Output a PDF file directly to the browser
            $mpdf->Output($name, $dest);
        }else
            return $mpdf->Output($name, $dest);

        return $name;

    }

    public function saveFile($name = '', $root = UPLOAD_DIR)
    {
        $this->render($name, $root);
        $name = $root . "$name";
// Output a PDF file directly to the browser
        $this->mpdf->Output($name, self::output_save_file);
    }

    public function setOrientation($ori)
    {
        $this->orientation = $ori;
        return $this;
    }
    public function addFont($font = 'GreatVibes-Regular.ttf', $name = 'greatvibes', $default = false)
    {
        $this->configs['fontdata'] = $this->fontData + [
                $name => [
                    'R' => $font,
                ]
            ];
        if ($default)
            $this->configs['default_font'] = $name;
        return $this;
    }
    public function setMargin($top = 0, $right = 0, $bottom = 0, $left = 0)
    {
        $this->margin_top = $top;
        $this->margin_right = $right;
        $this->margin_bottom = $bottom;
        $this->margin_left = $left;
        return $this;
    }
    const orientation_portrait = 'P';
    const orientation_landscape = 'L';
    const output_web = 'I';
    const output_download = 'D';
    const output_save_file = 'F';
    const output_source = 'S';

}
