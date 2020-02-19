<?php

/**
 * @author Jason F. Irwin
 * @copyright 2016
 *
 * Class contains the rules and methods called for Client Application Settings & Creation
 */
require_once( LIB_DIR . '/functions.php');

class Images {
    var $is_animated;
    var $image_type;
    var $is_reduced;
    var $image;
    var $exif;

    public function load($FileName) {
        $image_info = getimagesize($FileName);
        $this->exif = exif_read_data($FileName);
        $this->image_type = $image_info[2];
        $this->is_animated = false;
        $this->is_reduced = false;
        $rVal = false;

        // Load the Image into Memory
        switch ( $this->image_type ) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($FileName);
                $rVal = true;
                break;

            case IMAGETYPE_GIF:
                $this->is_animated = $this->_isAnimated($FileName);
                $this->image = imagecreatefromgif($FileName);
                $rVal = true;
                break;

            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($FileName);
                $clr = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
                imagecolortransparent($this->image, $clr);
                imageAlphaBlending($this->image, true);
                imageSaveAlpha($this->image, true);
                $rVal = true;
                break;

            case IMAGETYPE_WBMP:
            case IMAGETYPE_BMP:
                $this->image = imagecreatefromwbmp($FileName);
                $rVal = true;
                break;

            default:
                // Do Nothing
        }

        // Rotate the Image if Necessary
        if (!empty($this->exif['Orientation'])) {
            switch ($this->exif['Orientation']) {
                case 3:
                    $this->image = imagerotate($this->image, 180, 0);
                    break;

                case 6:
                    $this->image = imagerotate($this->image, -90, 0);
                    break;

                case 8:
                    $this->image = imagerotate($this->image, 90, 0);
                    break;
            }
        }

        // Return the Success Boolean
        return $rVal;
    }

    public function save($FileName, $compress = 100) {
        $rVal = false;

        switch ( $this->image_type ) {
            case IMAGETYPE_JPEG:
                $rVal = imagejpeg($this->image, $FileName, $compress);
                break;

            case IMAGETYPE_GIF:
                $rVal = imagegif($this->image, $FileName);
                break;

            case IMAGETYPE_PNG:
                $rVal = imagepng($this->image, $FileName);
                break;

            case IMAGETYPE_WBMP:
            case IMAGETYPE_BMP:
                $rVal = imagejpeg($this->image, $FileName, $compress);
                break;

            default:
                // Do Nothing
        }

        // Return the Success Boolean
        return $rVal;
    }

    public function getGeolocation() { return $this->_getGeolocation(); }
    public function getPhotoMeta() { return $this->_getPhotoMeta(); }

    public function returnExifData() { return $this->exif; }
    public function is_animated() { return $this->is_animated; }
    public function is_reduced() { return $this->is_reduced; }
    public function getWidth() { return imagesx($this->image); }
    public function getHeight() { return imagesy($this->image); }
    public function reduceToHeight($height = 480) {
        $propHeight = $this->getHeight();
        $propWidth = $this->getWidth();
        if ( $height >= $propHeight ) { return true; }
        $rVal = false;

        $ratio = $height / $propHeight;
        $width = $propWidth * $ratio;
        $rVal = $this->resize($height, $width);
        if ( $rVal ) { $this->is_reduced = true; }

        // Return the Boolean Response
        return $rVal;
    }
    public function makeSquare( $sizePx = 450, $min = 250 ) {
        $height = $this->getHeight();
        $width = $this->getWidth();

        if ( $height < $sizePx ) { $sizePx = $height; }
        if ( $width < $sizePx ) { $sizePx = $width; }

        writeNote("sizePx: $sizePx", true);

        $image = imagecreatetruecolor($sizePx, $sizePx);
        $isOK = false;

        $ratio = max($sizePx / $width, $sizePx / $height);
        writeNote("Ratio: $ratio", true);
        $y = ($height - $sizePx / $ratio) / 2;
        $height = $sizePx / $ratio;
        $x = ($width - $sizePx / $ratio) / 2;
        $width = $sizePx / $ratio;

        // Perform the Transformation and re-assign the Image
        $isOK = imagecopyresampled($image, $this->image, 0, 0, $x, $y, $sizePx, $sizePx, $width, $height);
        if ( $isOK ) {
            $this->image = $image;
            $this->is_reduced = true;
        }

        // Return the Boolean Response
        return $isOK;
    }
    public function reduceToWidth($width = 640) {
        $propHeight = $this->getHeight();
        $propWidth = $this->getWidth();
        if ( $width >= $propWidth ) { return true; }
        $rVal = false;

        $ratio = $width / $propWidth;
        $height = $propHeight * $ratio;
        $rVal = $this->resize($height, $width);
        if ( $rVal ) { $this->is_reduced = true; }

        // Return the Boolean Response
        return $rVal;
    }

    private function resize($height, $width) {
        $image = imagecreatetruecolor($width, $height);

        $isOK = imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        if ( $isOK ) {
            $this->image = $image;
            $this->is_reduced = true;
        }

        return $isOK;
    }

    private function _getPhotoMeta() {
        $aperture = false;
        if ( in_array('ApertureValue', $this->exif) ) {
            $apt_val = explode('/',$this->exif['ApertureValue']);
            $aperture = $apt_val[0] / $apt_val[1];
        }

        return array( 'make'     => ((NoNull($this->exif['Make']) != '') ? NoNull($this->exif['Make']) : false),
                      'model'    => ((NoNull($this->exif['Model']) != '') ? NoNull($this->exif['Model']) : false),
                      'exposure' => ((NoNull($this->exif['ExposureTime']) != '') ? NoNull($this->exif['ExposureTime']) : false),
                      'aperture' => $aperture,
                      'shutter'  => ((NoNull($this->exif['ShutterSpeedValue']) != '') ? NoNull($this->exif['ShutterSpeedValue']) : false),
                      'iso'      => ((nullInt($this->exif['ISOSpeedRatings']) > 0) ? nullInt($this->exif['ISOSpeedRatings']) : false),
                      'datetime' => ((NoNull($this->exif['DateTime']) != '') ? NoNull($this->exif['DateTime']) : false),
                      'width'    => ((nullInt($this->exif['ExifImageWidth']) > 0) ? nullInt($this->exif['ExifImageWidth'], imagesx($this->image)) : false),
                      'height'   => ((nullInt($this->exif['ExifImageLength']) > 0) ? nullInt($this->exif['ExifImageLength'], imagesy($this->image)) : false),
                     );
    }

    private function _getGeolocation() {
        if ( isset($this->exif['GPSLatitude']) && isset($this->exif['GPSLongitude']) &&
             isset($this->exif['GPSLatitudeRef']) && isset($this->exif['GPSLongitudeRef']) &&
             in_array($this->exif['GPSLatitudeRef'], array('E','W','N','S')) && in_array($this->exif['GPSLongitudeRef'], array('E','W','N','S')) ) {
            $direction = false;

            $GPSLatitudeRef  = strtoupper(NoNull($this->exif['GPSLatitudeRef']));
            $GPSLongitudeRef = strtoupper(NoNull($this->exif['GPSLongitudeRef']));

            $lat_degrees_a = explode('/', $this->exif['GPSLatitude'][0]);
            $lat_minutes_a = explode('/', $this->exif['GPSLatitude'][1]);
            $lat_seconds_a = explode('/', $this->exif['GPSLatitude'][2]);
            $lng_degrees_a = explode('/', $this->exif['GPSLongitude'][0]);
            $lng_minutes_a = explode('/', $this->exif['GPSLongitude'][1]);
            $lng_seconds_a = explode('/', $this->exif['GPSLongitude'][2]);
            $img_direction = explode('/', $this->exif['GPSImgDirection']);

            $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
            $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
            $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
            $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
            $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
            $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];
            if ( is_array($img_direction) && nullInt($img_direction[1]) > 0 ) { $direction = $img_direction[0] / $img_direction[1]; }

            $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
            $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);

            if ( $GPSLatitudeRef == 'S' ) { $lat *= -1; }           //If the latitude is South, make it negative.
            if ( $GPSLongitudeRef == 'W' ) { $lng *= -1; }          //If the longitude is west, make it negative

            return array (
                'latitude'  => nullInt($lat),
                'longitude' => nullInt($lng),
                'direction' => $direction,
            );
        }
        return false;
    }

    /**
     *  Function Returns a Boolean Response Stating Whether a File is Animated or Not
     */
    private function _isAnimated( $FileName ) {
        if(!($fh = @fopen($FileName, 'rb'))) return false;
        $count = 0;

        // Check to See if the Standard Animated GIF Chunk Exists (Identifying Frames)
        while(!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        fclose($fh);
        return $count > 1;
    }
}
?>