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

    protected function get_cached_path(){
        $path = pathinfo(fsencode_path($this->path, CACHE_ROOT));
        return $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename'] . '.jpg';
    }
    
    public function get_raw() {
        serve_file($this->get_file_path());
    }

    public function get_thumbnail(){
        $this->view_cached() or $this->generate_thumbnail();
    }
 
    /** Partially borrowed from cover website */
    protected function view_cached(){
        $file_path = $this->get_cached_path();
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
    protected function generate_thumbnail(){
        if (in_array(strtolower($this->type), $this->DOCUMENT_TYPES))
            $imagick = new imagick($this->get_file_path().'[0]');
        else if (in_array(strtolower($this->type), $this->IMAGE_TYPES))
            $imagick = new imagick($this->get_file_path().'[0]');
        else
            return true;

        $imagick->scaleImage(0, THUMBNAIL_HEIGHT);


        $imagick->setColorspace(COLORSPACE_SRGB);
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage( $this->get_cached_path() );
        $imagick->destroy();
        $this->view_cached();

        // // Oh shit cache not writable? Fall back to a temp stream.
        // $fout = $this->open_cache_stream($this->get_cached_path(), 'w+') or $fout = fopen('php://temp', 'w+');
        // rewind($fout);

        // // Write image to php output buffer
        // $imagick->setImageFormat('jpeg');
        // $imagick->writeImageFile($fout);
        // $imagick->destroy();

        // fseek($fout, 0, SEEK_END);
        // $file_size = ftell($fout);
        // rewind($fout);

        // serve_stream($fout, 'image/jpeg', $file_size);

        // // And clean up.
        // fclose($fout);
        return true;
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
