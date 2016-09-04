<?hh // strict

require_once ('NodeBase.php');

final class Tag extends NodeBase {

  private string $caption;
  private ?string $description;
  private int $creatorID;

  public function __construct(Map<string, string> $node) {
    parent::__construct($node);
    $data = json_decode($node['data'], true /*return array instead*/);
    $this->caption = $data['caption'];
    $this->description = array_key_exists('description', $data) ? $data['description'] : null;
    $this->creatorID = $data['creator_id'];
  }

  public function getCaption(): string {
    return $this->caption;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function getCreatorID(): int {
    return $this->creatorID;
  }
}