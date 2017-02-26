<?php
require_once 'include/init.php';

class ArchiveView extends TemplateView
{
    public function run() {
        if (!empty($_GET['poster'])) {
            // get poster or thumbnail!
        } else {
            if (!empty($_GET['path']) && $_GET['path'] !== '/')
                $this->path = fsencode_path($_GET['path']);
            else
                $this->path = $this->get_current_folder();
            echo $this->render_layout();            
        }
    }

    protected function list_folder($dir) {
        $dir .= DIRECTORY_SEPARATOR;
        $output = array();
        foreach (glob($dir.'*') as $item){
            $output[] = array(
                'type' => is_dir($item) ? 'dir' : 'file',
                'name' => urlencode_path($item)
            );
        }
        return $output;
    }

    protected function get_index() {
        return array_reverse(glob(ARCHIVE_ROOT . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR));
    }

    protected function get_current_folder() {
        return $this->get_index()[0];
    }

    protected function render_content() {
        $index = array_map('urlencode_path', $this->get_index());
        $posters = $this->list_folder($this->path);
        return $this->render_template('templates/archive.phtml', compact('index', 'posters'));
    }
}

$view = new ArchiveView('Poster Archive');
$view->run();
