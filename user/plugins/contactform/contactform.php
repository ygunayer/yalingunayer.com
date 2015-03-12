<?php namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;

class ContactFormPlugin extends Plugin {
  public static function getSubscribedEvents() {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  public function onPluginsInitialized() {
    if($this->isAdmin()) {
      $this->active = false;
      return;
    }

    $this->enable([
      'onTwigTemplatePaths'   => ['onTwigTemplatePaths', 0],
      'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
      'onPageInitialized'     => ['onPageInitialized', 0]
    ]);
  }

  public function onTwigTemplatePaths() {
    $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
  }

  public function onTwigSiteVariables() {
    if($this->grav['config']->get('plugins.contactform.enabled')) {
      $this->grav['assets']
        ->add('plugin://contactform/assets/css/contactform.css')
        ->add('plugin://contactform/assets/js/contactform.js');
    }
  }

  public function onPageInitialized() {
    $this->mergeConfig($this->grav['page']);

    if($this->grav['config']->get('plugins.contactform.enabled')) {
      $g = $this->grav;

      $page = $g["page"];
      $twig = $g["twig"];
      $uri = $g["uri"];

      $options = $g['config']->get('plugins.contactform');

      $old_content = $page->content();
      $status = FALSE;

      // some data was posted
      if($_SERVER["REQUEST_METHOD"] === "POST") {
        if(!$this->validate())
          $status = "error";
        else if($this->send())
          $status = "success";
        else
          $status = "fail";
      }

      $viewData = [
        "contactform" => $options,
        "page" => $page
      ];

      if($status) {
        $viewData["status"] = $status;
        $viewData["alertclass"] = $g["config"]->get("plugins.contactform.classes.status." . $status);
        $viewData["message"] = $g["config"]->get("plugins.contactform.messages.status." . $status);
      }

      $page->content($old_content . $twig->twig()->render("contactform/form.html.twig", $viewData));
    }
  }

  protected function validate() {
    $data = $_POST;
    $name = $data["name"];
    $email = filter_var($data["email"], FILTER_SANITIZE_EMAIL);
    $message = $data["message"];
    $antispam = $data["antispam"];

    return !empty($name) && !empty($message) && !empty($email) && $antispam == "42";
  }

  protected function send() {
    $data = $_POST;
    $name = $data["name"];
    $email = filter_var($data["email"], FILTER_SANITIZE_EMAIL);
    $message = $data["message"];
    
    $options = $this->grav['config']->get('plugins.contactform');
    $recipient = $options['recipient'];
    $subject = $options['subject'];

    $content = "Name: {$name}\n";
    $content .= "Email: {$email}\n\n";
    $content .= "Message:\n{$message}\n";

    $email_headers = "From: {$name} <{$email}>";

    $result = mail($recipient, $subject, $content, $email_headers);
    if(!$result) {
      $this->grav["log"]->error("FAILED TO SEND MAIL FROM {$name}, {$email}, ERROR WAS:\n", error_get_last());
    } else
      $this->grav["log"]->error("SUCCESSFULLY SENT MAIL FROM {$name}, {$email}");
    return $result;
  }

  protected function mergeConfig(Page $page) {
    $defaults = (array)$this->grav['config']->get('plugins.contactform');

    $h = $page->header();
    $g = $this->grav["config"];

    if(!isset($h->contactform)) {
        $g->set("plugins.contactform.enabled", false);
    } else {
      if(is_array($h->contactform))
        $g->set("plugins.contactform", array_replace_recursive($defaults, $h->contactform));
      else
        $g->set("plugins.contactform.enabled", $h->contactform);
    }
  }
}

