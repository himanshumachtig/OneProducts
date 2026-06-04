<?php

namespace Drupal\ai_domain_chatbot\EventSubscriber;

use Drupal\ai_assistant_api\AiAssistantApiRunner;
use Drupal\ai_domain_chatbot\Service\DomainSearcher;
use Drupal\ai_assistant_api\Event\AiAssistantSystemRoleEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Restricts AI chatbot responses to indexed content from configured domains.
 */
class DomainChatbotSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected AiAssistantApiRunner $runner,
    protected DomainSearcher $searcher,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AiAssistantSystemRoleEvent::EVENT_NAME => ['onAssistantMessage', 0],
    ];
  }

  /**
   * Inject domain-restricted context into the AI system prompt.
   */
  public function onAssistantMessage(AiAssistantSystemRoleEvent $event): void {
    $config  = $this->configFactory->get('ai_domain_chatbot.settings');
    $domains = array_filter(explode("\n", $config->get('domains') ?? ''));

    if (empty($domains)) {
      return;
    }

    $user_text   = $this->extractUserMessage();
    $domain_list = implode(', ', array_map('trim', $domains));

    $this->loggerFactory->get('ai_domain_chatbot')->info(
      'Domain chatbot firing. Query: "@q"',
      ['@q' => $user_text],
    );

    $system = "You are an intelligent AI assistant — respond exactly like ChatGPT would.\n\n"
      . "You have access to indexed content from these websites: {$domain_list}.\n\n"
      . "RESPONSE STYLE:\n"
      . "- Respond naturally and conversationally, like a knowledgeable human expert.\n"
      . "- Start with a brief 1-2 sentence introduction about the topic.\n"
      . "- Then give a thorough, well-structured answer covering ALL relevant details.\n"
      . "- Use bullet points, sections, or paragraphs — whatever best fits the answer.\n"
      . "- Include ALL specific details found: names, dates, numbers, technologies, features.\n"
      . "- Combine information from multiple pages and domains into one complete answer.\n"
      . "- Sound helpful, warm and professional — not robotic.\n\n"
      . "STRICT RULES:\n"
      . "- ONLY use information from the page content provided below.\n"
      . "- ONLY use URLs from the SOURCE URL lines. Never invent a URL.\n"
      . "- Always end with a 'Sources:' section listing pages you used.\n"
      . "- If content does not answer the question, say: 'I could not find this in the configured sources.'\n\n";

    if ($user_text && $user_text !== 'dummy_loading') {
      // Get more results and ensure domain diversity.
      $results = $this->searcher->search($user_text, $domains, 10);

      if (!empty($results)) {
        // Group by domain so Claude sees content organised by source.
        $by_domain = [];
        foreach ($results as $result) {
          $by_domain[$result['domain']][] = $result;
        }

        $system .= "=== CONTENT FROM ALL CONFIGURED WEBSITES ===\n\n";
        $page_num = 1;
        foreach ($by_domain as $domain => $pages) {
          $system .= "-- DOMAIN: {$domain} --\n\n";
          foreach ($pages as $result) {
            $system .= "PAGE {$page_num}:\n"
              . "SOURCE URL: {$result['url']}\n"
              . "TITLE: " . $this->clean($result['title']) . "\n"
              . "CONTENT: " . $this->clean($result['content']) . "\n"
              . "---\n\n";
            $page_num++;
          }
        }
        $system .= "Read ALL pages above from ALL domains. "
          . "Combine the most relevant information into one complete answer. "
          . "Include specific details like dates, names, numbers when present in the content.";
      }
      else {
        $system .= "No relevant pages were found across any of the configured websites for this query.\n"
          . "Respond: 'I could not find specific information about this in the configured sources.'";
      }
    }
    else {
      $system .= "Greet the user warmly and let them know they can ask any question about the content on the configured websites.";
    }

    $event->setSystemPrompt($system);
  }

  /**
   * Extract the current user message from the runner's message history.
   */
  private function extractUserMessage(): string {
    try {
      $history = $this->runner->getMessageHistory();
      foreach (array_reverse($history) as $msg) {
        $role = $msg['role'] ?? '';
        $text = $msg['message'] ?? '';
        if ($role === 'user' && $text && $text !== 'dummy_loading') {
          return $text;
        }
      }
    }
    catch (\Exception) {
      // Runner not ready — fall through.
    }
    return '';
  }

  /**
   * Strip invalid UTF-8 and control characters that the API rejects.
   */
  private function clean(string $text): string {
    $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean ?: $text);
    return $clean ?? $text;
  }

}
