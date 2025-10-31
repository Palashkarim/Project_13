<?php
namespace Iot\Models;

use PDO;

/**
 * Widget model (optional DB-backed catalog)
 * - widgets(key TEXT PK, name, category, meta JSONB)
 * If you keep a static catalog in code, you can skip this model.
 */
class Widget {
  public function __construct(private PDO $pdo) {}

  public function all(): array {
    return $this->pdo->query('SELECT key, name, category, meta FROM widgets ORDER BY key')->fetchAll();
  }

  public function upsert(string $key, string $name, string $category, array $meta = []): bool {
    $st = $this->pdo->prepare("INSERT INTO widgets(key, name, category, meta)
                               VALUES(:k,:n,:c,:m)
                               ON CONFLICT (key) DO UPDATE SET name=EXCLUDED.name, category=EXCLUDED.category, meta=EXCLUDED.meta");
    return $st->execute([':k'=>$key, ':n'=>$name, ':c'=>$category, ':m'=>json_encode($meta)]);
  }
}
