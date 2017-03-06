<?php 

require_once 'include/config.php';
require_once 'include/functions.php';


/**
 * CachedPoster: A class to manage a file and its thumbnail cache
 */
class CachedPoster
{
    private $IMAGE_TYPES = array('bmp', 'eps', 'gif', 'jpg', 'jpeg', 'png', 'tif');
    private $DOCUMENT_TYPES = array('pdf', 'psd');

    public function __construct($path){
        $this->path = $path;
        $this->type = pathinfo($path)['extension'];
    }

    /** Returns path of the source file on disk */
    protected function get_file_path(){
        return fsencode_path($this->path);
    }

    /** Returns path of the cached thumbnail on disk */
    protected function get_cached_path($width, $height){
        $path = pathinfo(fsencode_path($this->path, CACHE_ROOT));
        $cached_name = sprintf('%s_%s_%s.jpg', $path['filename'], $width, $height);
        return $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename'] . DIRECTORY_SEPARATOR . $cached_name ;
    }
    
    /**  Serves source file to client */
    public function get_raw() {
        $this->serve_file($this->get_file_path());
    }

    /**  Generates cached thumbnail and serves to client */
    public function get_thumbnail(){
        $width = defined('THUMBNAIL_WIDTH') ? THUMBNAIL_WIDTH : 0;
        $height = defined('THUMBNAIL_HEIGHT') ? THUMBNAIL_HEIGHT : 0;
        $this->view_cached($width, $height) or $this->generate_thumbnail($width, $height);
    }
 
    /** View a chached thumbnail (partially borrowed from cover website) */
    protected function view_cached($width, $height){
        $cached_file_path = $this->get_cached_path($width, $height);

        if (!file_exists($cached_file_path))
            return false;

        // Send an extra header with the mtime to make debugging the cache easier
        header('X-Cache: ' . date('r', filemtime($cached_file_path)));

        $this->serve_file($cached_file_path);

        // Let them know we succeeded, no need to generate a new image.
        return true;
    }

    /** Generates a new thumbnail (and serves it to the client) */
    protected function generate_thumbnail($width, $height){
        // Open poster, get first page of complex poster
        if (in_array(strtolower($this->type), $this->DOCUMENT_TYPES))
            $imagick = new imagick($this->get_file_path().'[0]');
        else if (in_array(strtolower($this->type), $this->IMAGE_TYPES))
            $imagick = new imagick($this->get_file_path());
        else
            return false;

        $cur_height = $imagick->getImageHeight();
        $cur_width = $imagick->getImageWidth();

        // Crop image
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

        // Resize image, bestfit only works if both dimensions are given
        $bestfit = $width != 0 && $height != 0;
        $imagick->scaleImage($width, $height, $bestfit);

        $filename = $this->get_cached_path($width, $height);

        // Make sure output directory exists
        if (!file_exists(dirname($filename)))
            mkdir(dirname($filename), 0777, true);

        // Write image to cache
        $imagick->setColorspace(Imagick::COLORSPACE_SRGB);
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage( $filename );
        $imagick->destroy();
        
        // Send cached image to client
        return $this->view_cached($width, $height);
    }
    
    /** Serves a file from disk to client */
    protected function serve_file($file){
        if ($content_type = get_mime_type($file))
            header('Content-Type: ' . $content_type);
        else {
            $name = pathinfo($file, PATHINFO_FILENAME);
            header('Content-Disposition: attachment; filename="' . $name . '"');
        }

        header(sprintf('Content-Length: %d', filesize($file)));

        $fout = fopen($file, 'rb');
        fpassthru($fout);
        fclose($fout);
    }
}
