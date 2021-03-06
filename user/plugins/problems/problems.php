<?php
namespace Grav\Plugin;

use Grav\Common\Cache;
use Grav\Common\Plugin;
use Grav\Common\Uri;

class ProblemsPlugin extends Plugin
{
    protected $results = array();

    protected $check;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onFatalException' => ['onFatalException', 0]
        ];
    }

    public function onFatalException()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        // Run through potential issues
        if ($this->problemChecker()) {
            $this->renderProblems();
        }
    }

    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        /** @var Cache $cache */
        $cache = $this->grav['cache'];
        $validated_prefix = 'problem-check-';

        $this->check = CACHE_DIR . $validated_prefix . $cache->getKey();

        if (!file_exists($this->check)) {

            // If no issues remain, save a state file in the cache
            if (!$this->problemChecker()) {

                // delete any existing validated files
                foreach (new \GlobIterator(CACHE_DIR . $validated_prefix . '*') as $fileInfo) {
                    @unlink($fileInfo->getPathname());
                }

                // create a file in the cache dir so it only runs on cache changes
                touch($this->check);

            } else {
                $this->renderProblems();
            }

        }
    }

    protected function renderProblems()
    {
        $theme = 'antimatter';

        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        $baseUrlRelative = $uri->rootUrl(false);
        $themeUrl = $baseUrlRelative . '/' . USER_PATH . basename(THEMES_DIR) . '/' . $theme;
        $problemsUrl = $baseUrlRelative . '/user/plugins/problems';

        $html = file_get_contents(__DIR__ . '/html/problems.html');

        $problems = '';
        foreach ($this->results as $key => $result) {

            if ($key == 'files') {
                foreach ($result as $filename => $file_result) {
                    foreach ($file_result as $status => $text) {
                        $problems .= $this->getListRow($status, '<b>' . $filename . '</b> ' . $text);
                    }
                }
            } else {
                foreach ($result as $status => $text) {
                    $problems .= $this->getListRow($status, $text);
                }
            }
        }

        $html = str_replace('%%BASE_URL%%', $baseUrlRelative, $html);
        $html = str_replace('%%THEME_URL%%', $themeUrl, $html);
        $html = str_replace('%%PROBLEMS_URL%%', $problemsUrl, $html);
        $html = str_replace('%%PROBLEMS%%', $problems, $html);

        echo $html;

        exit();


    }

    protected function getListRow($status, $text)
    {
        if ($status == 'error') {
            $icon = 'fa-times';
        } elseif ($status == 'info') {
            $icon = 'fa-info';
        } else {
            $icon = 'fa-check';
        }
        $output = "\n";
        $output .= '<li class="' . $status . ' clearfix"><i class="fa fa-fw '. $icon . '"></i><p>'. $text . '</p></li>';
        return $output;
    }

    protected function problemChecker()
    {
        $min_php_version = '5.4.0';
        $problems_found = false;

        $essential_files = [
            '.htaccess' => false,
            'cache' => true,
            'logs' => true,
            'images' => true,
            'assets' => true,
            'system' => false,
            'user/data' => true,
            'user/pages' => false,
            'user/config' => false,
            'user/plugins/error' => false,
            'user/plugins' => false,
            'user/themes' => false,
            'vendor' => false
        ];

        // Check PHP version
        if (version_compare(phpversion(), '5.4.0', '<')) {
            $problems_found = true;
            $php_version_adjective = 'lower';
            $php_version_status = 'error';

        } else {
            $php_version_adjective = 'greater';
            $php_version_status = 'success';
        }
        $this->results['php'] = [$php_version_status => 'Your PHP version (' . phpversion() . ') is '. $php_version_adjective . ' than the minimum required: <b>' . $min_php_version . '</b>'];

        // Check for GD library
        if (defined('GD_VERSION') && function_exists('gd_info')) {
            $gd_adjective = '';
            $gd_status = 'success';
        } else {
            $problems_found = true;
            $gd_adjective = 'not ';
            $gd_status = 'error';
        }
        $this->results['gd'] = [$gd_status => 'PHP GD (Image Manipulation Library) is '. $gd_adjective . 'installed'];

        // Check for essential files & perms
        $file_problems = [];
        foreach ($essential_files as $file => $check_writable) {
            $file_path = ROOT_DIR . $file;
            $is_dir = false;
            if (!file_exists($file_path)) {
                $problems_found = true;
                $file_status = 'error';
                $file_adjective = 'does not exist';

            } else {
                $file_status = 'success';
                $file_adjective = 'exists';
                $is_writeable = is_writable($file_path);
                $is_dir = is_dir($file_path);

                if ($check_writable) {
                    if (!$is_writeable) {
                        $file_status = 'error';
                        $problems_found = true;
                        $file_adjective .= ' but is <b class="underline">not writeable</b>';
                    } else {
                        $file_adjective .= ' and <b class="underline">is writeable</b>';
                    }
                }
            }

            $file_problems[$file_path] = [$file_status => $file_adjective];

        }
        if (sizeof($file_problems) > 0) {

            $this->results['files'] = $file_problems;
        }

        return $problems_found;
    }
}
