<?hh // strict

require_once ('NodeBase.php');
require_once ('metadata/TaskStatus.php');

final class Task extends NodeBase {

  private TaskStatus $status;
  private string $title;
  private ?string $description;
  private ?int $ownerID;
  private Priority $priority;

  public function __construct(Map<string, string> $node) {
    parent::__construct($node);
    $data = json_decode($node['data'], true /*return array instead*/);
    $this->status = TaskStatus::assert($data['status']);
    $this->title = $data['title'];
    $this->description = idx($data, 'description') ?: null;
    $this->ownerID = idx($data, 'owner_id') ?: null;
    $this->priority =  idx($data, 'priority') ? Priority::assert($data['priority']) : Priority::UNSPECIFIED;
  }

  public function getStatus(): TaskStatus {
    return $this->status;
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function getOwnerID(): ?int {
    return $this->ownerID;
  }

  public function getPriority(): Priority {
    return $this->priority;
  }
}
