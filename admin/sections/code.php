<?php
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO code_links (title, url, description, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    trim($_POST['title']),
                    trim($_POST['url']),
                    trim($_POST['description'] ?? ''),
                    (int)($_POST['sort_order'] ?? 0)
                ]);
                $success = "Ссылка успешно добавлена";
                break;
            case 'update':
                $stmt = $pdo->prepare("UPDATE code_links SET title = ?, url = ?, description = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([
                    trim($_POST['title']),
                    trim($_POST['url']),
                    trim($_POST['description'] ?? ''),
                    (int)($_POST['sort_order'] ?? 0),
                    (int)$_POST['id']
                ]);
                $success = "Ссылка успешно обновлена";
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM code_links WHERE id = ?");
                $stmt->execute([(int)$_POST['id']]);
                $success = "Ссылка успешно удалена";
                break;
            case 'toggle_active':
                $stmt = $pdo->prepare("UPDATE code_links SET is_active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['is_active'] === '1' ? 1 : 0,
                    (int)$_POST['id']
                ]);
                $success = "Статус ссылки обновлен";
                break;
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
try {
    $stmt = $pdo->query("SELECT * FROM code_links ORDER BY sort_order ASC, created_at DESC");
    $links = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Ошибка при получении списка ссылок: " . $e->getMessage();
    $links = [];
}
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
?>
<div class="container">
    <h1 class="mb-4">Управление ссылками на код</h1>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Добавить новую ссылку</h5>
            <form method="POST" id="addLinkForm">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Название ссылки *</label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="Например: Основной репозиторий проекта">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="url" class="form-label">URL ссылки *</label>
                            <input type="url" class="form-control" id="url" name="url" required 
                                   placeholder="https://github.com/username/repository">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание (опционально)</label>
                            <textarea class="form-control" id="description" name="description" rows="2" 
                                      placeholder="Краткое описание репозитория или проекта"></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Порядок сортировки</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="0" min="0" placeholder="0">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить ссылку
                </button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Список ссылок</h5>
            <?php if (empty($links)): ?>
                <p class="text-muted">Ссылок пока нет</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Порядок</th>
                                <th>Название</th>
                                <th>URL</th>
                                <th>Описание</th>
                                <th>Статус</th>
                                <th>Создана</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($links as $link): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $link['sort_order']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($link['title']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($link['url']); ?>
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($link['description']): ?>
                                            <span class="text-muted"><?php echo htmlspecialchars($link['description']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($link['is_active']): ?>
                                            <span class="badge bg-success">Активна</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Неактивна</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($link['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $link['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Вы уверены, что хотите удалить эту ссылку?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editModal<?php echo $link['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Редактирование ссылки</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_title<?php echo $link['id']; ?>" class="form-label">Название ссылки *</label>
                                                                <input type="text" class="form-control" 
                                                                       id="edit_title<?php echo $link['id']; ?>" 
                                                                       name="title" 
                                                                       value="<?php echo htmlspecialchars($link['title']); ?>" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_url<?php echo $link['id']; ?>" class="form-label">URL ссылки *</label>
                                                                <input type="url" class="form-control" 
                                                                       id="edit_url<?php echo $link['id']; ?>" 
                                                                       name="url" 
                                                                       value="<?php echo htmlspecialchars($link['url']); ?>" 
                                                                       required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $link['id']; ?>" class="form-label">Описание</label>
                                                                <textarea class="form-control" 
                                                                          id="edit_description<?php echo $link['id']; ?>" 
                                                                          name="description" 
                                                                          rows="2"><?php echo htmlspecialchars($link['description']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="edit_sort_order<?php echo $link['id']; ?>" class="form-label">Порядок сортировки</label>
                                                                <input type="number" class="form-control" 
                                                                       id="edit_sort_order<?php echo $link['id']; ?>" 
                                                                       name="sort_order" 
                                                                       value="<?php echo $link['sort_order']; ?>" 
                                                                       min="0">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.getElementById('addLinkForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const url = document.getElementById('url').value.trim();
            if (!title) {
                e.preventDefault();
                alert('Пожалуйста, введите название ссылки');
                return false;
            }
            if (!url) {
                e.preventDefault();
                alert('Пожалуйста, введите URL ссылки');
                return false;
            }
            if (!isValidUrl(url)) {
                e.preventDefault();
                alert('Пожалуйста, введите корректный URL');
                return false;
            }
        });
    }
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});
</script> 