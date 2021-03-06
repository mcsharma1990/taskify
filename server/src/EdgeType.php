<?hh // strict

enum EdgeType: int {
    USER_TO_OWNED_TASK = 1;
    TASK_TO_OWNER = 2;

    USER_TO_CREATED_TASK = 3;
    TASK_TO_CREATOR = 4;

    TASK_TO_TAG = 5;
    TAG_TO_TASK = 6;

    TASK_TO_SUBSCRIBER = 7;
    USER_TO_SUBSCRIBED_TASK = 8;

    TASK_TO_ACTIVITY = 9;
    ACTIVITY_TO_TASK = 10;
    
    ACTOR_TO_ACTIVITY = 11;
    ACTIVITY_TO_ACTOR = 12;
}

abstract final class EdgeUtil {

  private static ImmMap<EdgeType, EdgeType> $inverseEdge = ImmMap {
    EdgeType::USER_TO_OWNED_TASK => EdgeType::TASK_TO_OWNER,
    EdgeType::TASK_TO_OWNER => EdgeType::USER_TO_OWNED_TASK,

    EdgeType::USER_TO_CREATED_TASK => EdgeType::TASK_TO_CREATOR,
    EdgeType::TASK_TO_CREATOR => EdgeType::USER_TO_CREATED_TASK,

    EdgeType::TASK_TO_TAG => EdgeType::TAG_TO_TASK,
    EdgeType::TAG_TO_TASK => EdgeType::TASK_TO_TAG,

    EdgeType::TASK_TO_SUBSCRIBER => EdgeType::USER_TO_SUBSCRIBED_TASK,
    EdgeType::USER_TO_SUBSCRIBED_TASK => EdgeType::TASK_TO_SUBSCRIBER,

    EdgeType::TASK_TO_ACTIVITY => EdgeType::ACTIVITY_TO_TASK,
    EdgeType::ACTIVITY_TO_TASK => EdgeType::TASK_TO_ACTIVITY,

    EdgeType::ACTOR_TO_ACTIVITY => EdgeType::ACTIVITY_TO_ACTOR,
    EdgeType::ACTIVITY_TO_ACTOR => EdgeType::ACTOR_TO_ACTIVITY,
  };

  public static function getInverse(EdgeType $edgeType): ?EdgeType {
    return self::$inverseEdge->contains($edgeType)
      ? self::$inverseEdge[$edgeType]
      : null;
  }
}
