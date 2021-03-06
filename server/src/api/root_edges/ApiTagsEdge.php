<?hh // strict


final class ApiTagsEdge extends ApiRootEdgeBase<Tag> {

  public function getRootEdgeClass(): classname<TagsEdge> {
    return TagsEdge::class;
  }

  public function getTargetNodeClass(): classname<ApiTagNode> {
    return ApiTagNode::class;
  }
}
