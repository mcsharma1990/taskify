<?hh // strict

require('IDUtil.php');
require('NodeType.php');
require('EdgeType.php');
require_once('ServerConfig.php');

final class TaskifyDB {

    const int MAX_FETCH_LIMIT = 10000;

    private static async function genConnection(): Awaitable<\AsyncMysqlConnection> {
        // Get a connection pool with default options
        $pool = new \AsyncMysqlConnectionPool(array());
        return await $pool->connect(
          ServerConfig::getDBHost(),
          3306,
          'taskify',
          'root',
          ServerConfig::getDBPassword(),
        );
    }

    public static async function genNode(int $id): Awaitable<Map<string, string>> {
        $table = IDUtil::idToTable($id);
        $conn = await self::genConnection();
        // Note: There seems to be a problem with queryf() function. It is
        // apparently crashing hhvm and no stacktrace provided. That's why
        // queryf is used everywhere.
        $result = await $conn->query('SELECT * from '.$table.' WHERE id = '.$id);
        // There shouldn't be more than one row returned for one user id
        invariant($result->numRows() === 1, 'one row exactly');
        // A vector of vector objects holding the string values of each column
        // in the query
        return $result->mapRows()[0];
    }

    public static async function genNodesForType(
      NodeType $nodeType,
      int $limit = self::MAX_FETCH_LIMIT,
    ): Awaitable<Vector<Map<string, string>>> {
        $table = IDUtil::typeToTable($nodeType);
        $conn = await self::genConnection();
        $limit = min($limit, self::MAX_FETCH_LIMIT);
        // Note: There seems to be a problem with queryf() function. It is
        // apparently crashing hhvm and no stacktrace provided. That's why
        // queryf is used everywhere.
        $result = await $conn->query(sprintf("SELECT * from %s LIMIT %d", $table, $limit));
        // A vector of vector objects holding the string values of each column
        // in the query

        return $result->mapRows();
    }

    /**
     * Returns null if edge doesn't exist, otherwise returns Map containting
     * edge data.
     */
    public static async function genEdge(
      int $id1,
      int $id2,
      int $edgeType,
    ): Awaitable<?Map<string, string>> {
      $conn = await self::genConnection();
      $result = await $conn->query(
        'SELECT id2, created_time, updated_time, data from edge WHERE type = '.
          $edgeType.
          ' AND id1 = '.
          $id1.
          ' AND id2 = '.
          $id2
      );
      invariant($result->numRows() <= 1, "Shouldn't be more than one edge");
      if ($result->numRows() === 1) {
        // edge exists
        return $result->mapRows()[0];
      }
      return null;
    }

    public static async function genEdgeExists(
      int $id1,
      int $id2,
      EdgeType $edgeType,
    ): Awaitable<bool> {
      $conn = await self::genConnection();
      $response = await $conn->query(
        sprintf('SELECT COUNT(1) FROM edge WHERE id1 = %d AND id2 = %d AND type = %d',
        $id1,
        $id2,
        (int)$edgeType,
      ));
      return $response->vectorRows()[0][0] > 0;
    }

    public static async function genEdgesForType(
      int $id1,
      EdgeType $edgeType,
    ): Awaitable<Vector<Map<string, string>>> {
      $conn = await self::genConnection();
      $result = await $conn->query(
        'SELECT id2, created_time, updated_time, data from edge WHERE type = '.
          $edgeType.
          ' AND id1 = '.
          $id1
      );
      return $result->mapRows();
    }

    public static async function genCreateNode(
      NodeType $node_type,
      Map<string, mixed> $fields,
    ): Awaitable<int> {
      $conn = await self::genConnection();
      $table = IDUtil::typeToTable($node_type);
      $data = json_encode($fields);
      $q = sprintf("INSERT INTO %s (data) VALUES ('%s')", $table, $data);
      $res = await $conn->query($q);
      return $res->lastInsertId();
    }


    public static async function genUpdateNode(
      int $node_id,
      Map<string, mixed> $fields,
    ): Awaitable<void> {
      if ($fields->count() === 0) {
        return;
      }
      $conn = await self::genConnection();
      $table = IDUtil::idToTable($node_id);
      $update_strings = Vector {};
      foreach ($fields as $field => $value) {
        $key_str = sprintf('"$.%s"', $field);
        $value_str = "";
        if (is_string($value)) {
          $value_str = sprintf('"%s"', $value);
        } else if (is_int($value)) {
          $value_str = (string)$value;
        } else  {
          invariant_violation('Unimplemented field type found in in genUpdateNode');
        }
        $update_strings[] = $key_str.", ".$value_str;
      }
      $update_string = implode(', ', $update_strings);
      $q = sprintf(
        "UPDATE %s SET data = JSON_SET(data, %s) WHERE id = %d",
        $table,
        $update_string,
        $node_id,
      );
      $res = await $conn->query($q);
    }

    public static async function genCreateEdge(
      EdgeType $edge_type,
      int $id1,
      int $id2,
      ?Map<string, mixed> $data = null,
    ): Awaitable<void> {
      $inverse_type = EdgeUtil::getInverse($edge_type);
      $gens = Vector {
        self::genEdgeExists($id1, $id2, $edge_type)
      };
      if ($inverse_type !== null) {
        $gens[] = self::genEdgeExists($id2, $id1, $inverse_type);
      }
      $results = await \HH\Asio\v($gens);
      if ($results[0] || ($inverse_type !== null && $results[1])) {
        throw new Exception('Edge already exists');
      }

      $json_data = json_encode($data);
      $conn = await self::genConnection();
      if ($inverse_type !== null) {
        await $conn->query(sprintf(
          "INSERT INTO edge (id1, id2, type, data) VALUES (%d, %d, %d, '%s'), (%d, %d, %d, '%s')",
          $id1, $id2, (int)$edge_type, $json_data,
          $id2, $id1, (int)$inverse_type, $json_data
        ));
      } else {
        await $conn->query(sprintf(
          "INSERT INTO edge (id1, id2, type, data) VALUES (%d, %d, %d, '%s')",
          $id1, $id2, (int)$edge_type, $json_data,
        ));
      }
    }

    /**
     * Returns data from 'hash' table, for a given hash_type and key
     */
     // TODO convert hash type to enum
    public static async function genHashValue(
      string $hash_type,
      string $key,
    ): Awaitable<?Map<string, mixed>> {
      $conn = await self::genConnection();
      $result = await $conn->query(sprintf(
        "SELECT value from hash WHERE type = '%s' AND `key` = '%s'",
        $hash_type,
        $key
      ));
      // There shouldn't be more than one row returned for one user id
      invariant($result->numRows() <= 1, 'Must be at most 1 row');
      if ($result->numRows() === 0) {
        return null;
      }
      // A vector of vector objects holding the string values of each column
      // in the query
      return new Map(json_decode($result->mapRows()[0]['value'], true));
    }

    public static async function genSetHash(
      string $has_type,
      string $key, Map<string, mixed> $value,
    ): Awaitable<void> {
      $conn = await self::genConnection();
      $encoded_value = json_encode($value);
      await $conn->query(sprintf(
        "INSERT INTO hash (type, `key`, value) VALUES('%s', '%s', '%s') ON DUPLICATE KEY UPDATE value='%s'",
        $has_type,
        $key,
        $encoded_value,
        $encoded_value,
      ));
    }
}
