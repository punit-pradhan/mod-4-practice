<?php 

namespace Drupal\custom_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomApiController extends ControllerBase {

  public function displayData(Request $request) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'blogs')
      ->condition('status', 1) // Published nodes
      ->accessCheck(FALSE); // Disable access check

    // Date Range parameter
    $start_date = $request->query->get('start_date');
    $end_date = $request->query->get('end_date');
    if ($start_date && $end_date) {
      $query->condition('created', strtotime($start_date), '>=');
      $query->condition('created', strtotime($end_date), '<=');
    }

    // Specific Authors parameter by name
    $specific_authors = $request->query->get('specific_authors');
    if ($specific_authors) {
      $author_ids = $this->getUserIdsByAuthorNames($specific_authors);
      if (!empty($author_ids)) {
        $query->condition('uid', $author_ids, 'IN');
      }
    }

    // Specific Tags parameter by name
    $specific_tags = $request->query->get('specific_tags');
    if ($specific_tags) {
      $tag_ids = $this->getTagIdsByTagNames($specific_tags);
      if (!empty($tag_ids)) {
        $query->condition('field_tags', $tag_ids, 'IN');
      }
    }

    $nids = $query->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

    $data = [];
    foreach ($nodes as $node) {
      $author = $node->getOwner();
      $published_date = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'd-m-Y');

      // Load taxonomy term tags
      $tags = [];
      $tag_entities = $node->get('field_tags')->referencedEntities();
      foreach ($tag_entities as $tag_entity) {
        $tags[] = $tag_entity->getName();
      }

      $data[] = [
        'title' => $node->getTitle(),
        'body' => $node->get('field_body')->value,
        'published_date' => $published_date,
        'author_name' => $author ? $author->getDisplayName() : '',
        'tags' => $tags,
      ];
    }

    $response = new JsonResponse($data);
    return $response;
  }

  /**
   * Get user IDs by author names.
   */
  protected function getUserIdsByAuthorNames($author_names) {
    $query = \Drupal::entityQuery('user')
      ->condition('name', $author_names, 'IN')
      ->accessCheck(FALSE);
    return $query->execute();
  }

  /**
   * Get taxonomy term IDs by tag names.
   */
  protected function getTagIdsByTagNames($tag_names) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $tag_names, 'IN')
      ->condition('vid', 'tags') // Assuming 'tags' is the vocabulary machine name
      ->accessCheck(FALSE);
    return $query->execute();
  }
}
