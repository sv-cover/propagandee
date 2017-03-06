<?php

define('THUMBNAIL_WIDTH', 800);

require_once 'include/init.php';
require_once 'include/CachedPoster.class.php';

/**
 * ArchiveView: A class to render the poster archive and serve archived posters
 * Folders or files starting with an '_' are considered private and won't be shown.
 */
class ArchiveView extends TemplateView
{
    /** Run the view */
    public function run() {
        if (!empty($_GET['poster'])) {
            // Serve requested poster or thumbnail
            $poster = new CachedPoster(urldecode($_GET['poster']));
            if (!empty($_GET['view']) && $_GET['view'] === 'thumbnail') 
                $poster->get_thumbnail();
            else
                $poster->get_raw();
        } else {
            // Render requested folder (or default folder if no path set)
            if (!empty($_GET['path']) && $_GET['path'] !== '/')
                $this->path = fsencode_path($_GET['path']);
            else
                $this->path = $this->get_current_folder();
            echo $this->render_layout();            
        }
    }

    /** Render the page content */
    protected function render_content() {
        $index = $this->get_index();
        $posters = $this->list_folder($this->path);
        $path = urlencode_path($this->path);
        return $this->render_template('templates/archive.phtml', compact('index', 'posters', 'path'));
    }

    /** Returns array with all visible files and folders within a folder */
    protected function list_folder($dir) {
        $dir .= DIRECTORY_SEPARATOR;
        $output = array();
        foreach (glob($dir.'[!_]*') as $item){
            $output[] = array_merge(
                array(
                    'type' => is_dir($item) ? 'dir' : 'file',
                    'path' => urlencode_path($item),
                ),
                pathinfo($item)
            );
        }
        return $output;
    }

    /** Returns array with all visible folders in the archive root */
    protected function get_index() {
        $dirs = glob(ARCHIVE_ROOT .  DIRECTORY_SEPARATOR . '[!_]*', GLOB_ONLYDIR);
        $output = array();
        foreach (array_reverse($dirs) as $item){
            $output[] = array_merge(
                array(
                    'path' => urlencode_path($item)
                ),
                pathinfo($item)
            );
        }
        return $output;
    }

    /** Returns the current home folder of the archive */
    protected function get_current_folder() {
        $dir = glob(ARCHIVE_ROOT . DIRECTORY_SEPARATOR . '[!_]*', GLOB_ONLYDIR);
        return end($dir);
    }
}

// Create and run archive view
$view = new ArchiveView('Poster Archive', 'archive');
$view->run();
