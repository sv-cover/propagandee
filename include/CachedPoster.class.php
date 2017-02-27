<?php 
require_once 'include/config.php';
require_once 'include/utils.php';

class CachedPoster
{
    private $IMAGE_TYPES = array('bmp', 'eps', 'gif', 'jpg', 'jpeg', 'png', 'tif');
    private $DOCUMENT_TYPES = array('pdf', 'psd');

    public function __construct($path){
        $this->path = $path;
        $this->type = pathinfo($path)['extension'];
    }

    protected function get_file_path(){
        return fsencode_path($this->path);
    }

    protected function get_cached_path($width, $height){
        $path = pathinfo(fsencode_path($this->path, CACHE_ROOT));
        $cached_name = $path['filename'] . '_' . $width . '_' . $height;
        return $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename'] . DIRECTORY_SEPARATOR . $cached_name . '.jpg';
    }
    
    public function get_raw() {
        serve_file($this->get_file_path());
    }

    public function get_thumbnail(){
        $width = defined('THUMBNAIL_WIDTH') ? THUMBNAIL_WIDTH : 0;
        $height = defined('THUMBNAIL_HEIGHT') ? THUMBNAIL_HEIGHT : 0;
        $this->view_cached($width, $height) or $this->generate_thumbnail($width, $height);
    }
 
    /** Partially borrowed from cover website */
    protected function view_cached($width, $height){
        $file_path = $this->get_cached_path($width, $height);
        if (!($fh = $this->open_cache_stream($file_path, 'rb')))
            return false;
        // Send an extra header with the mtime to make debugging the cache easier
        header('X-Cache: ' . date('r', filemtime($file_path)));

        serve_stream($fh, 'image/jpeg', filesize($file_path));
        fclose($fh);

        // Let them know we succeeded, no need to generate a new image.
        return true;
    }

    /** Partially borrowed from cover website */
    protected function generate_thumbnail($width, $height){
        if (in_array(strtolower($this->type), $this->DOCUMENT_TYPES))
            $imagick = new imagick($this->get_file_path().'[0]');
        else if (in_array(strtolower($this->type), $this->IMAGE_TYPES))
            $imagick = new imagick($this->get_file_path());
        else
            return true;

        $cur_height = $imagick->getImageHeight();
        $cur_width = $imagick->getImageWidth();

        if ( $cur_height > $cur_width && $cur_height / $cur_width > 1.4143) {
            // Image is higher then portrait A4-paper ( 1:sqrt(2) )
            $new_height = $cur_width * sqrt(2);
            $y = (int)($cur_height/2) - (int)($new_height/2);
            $imagick->cropImage($cur_width, $new_height, 0, $y);
        } else if ($cur_height < $cur_width && $cur_width / $cur_height > 1.78) {
            // Image is wider then 16:9
            $new_width = $cur_height * (16/9);
            $x = (int)($cur_width/2) - (int)($new_width/2);
            var_dump($x);
            $imagick->cropImage($new_width, $cur_height, $x, 0);
        }

        $bestfit = $width != 0 && $height != 0;
        $imagick->scaleImage($width, $height, $bestfit);

        $path = $this->get_cached_path($width, $height);

        if (!file_exists(dirname($path)))
            mkdir(dirname($path), 0777, true);
        
        // Write image to php output buffer
        $imagick->setColorspace(Imagick::COLORSPACE_SRGB);
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage( $path );
        $imagick->destroy();
        
        return $this->view_cached($width, $height);

    }
    
    /** Borrowed from cover website */
    protected function open_cache_stream($path, $mode) {
        if ($path === null)
            return null;

        if (!file_exists($path)) {
            // If we were trying to read, stop trying, it won't work, the file does not exist
            if ($mode{0} == 'r')
                return null;

            // However, if we were trying to write, make sure the directory exists and make it otherwise.
            if ($mode{0} == 'w' && !file_exists(dirname($path)))
                mkdir(dirname($path), 0777, true);
        }

        return fopen($path, $mode);
    }
}
