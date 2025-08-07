<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
$pageTitle = "Код проекта";
$pageDescription = "Ссылки на репозитории кода проекта ФитоДомик";
$pageKeywords = "код, репозиторий, GitHub, исходный код, ФитоДомик";
try {
    $stmt = $pdo->query("SELECT * FROM code_links WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC");
    $links = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Ошибка при получении ссылок на код: " . $e->getMessage());
    $links = [];
}
ob_start();
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-4">
                <i class="fas fa-code text-primary"></i> 
                Код проекта ФитоДомик
            </h1>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <p class="lead">
                        Здесь собраны ссылки на репозитории с исходным кодом проекта ФитоДомик. 
                        Вы можете изучить код, внести свой вклад или использовать его в своих проектах.
                    </p>
                </div>
            </div>
            <?php if (empty($links)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i>
                    <h4 class="alert-heading">Ссылки на код пока не добавлены</h4>
                    <p class="mb-0">
                        Администратор еще не добавил ссылки на репозитории с кодом. 
                        Загляните позже!
                    </p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($links as $link): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 link-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="link-icon me-3">
                                            <?php
                                            $url = strtolower($link['url']);
                                            if (strpos($url, 'github.com') !== false) {
                                                echo '<i class="fab fa-github fa-2x text-dark"></i>';
                                            } elseif (strpos($url, 'gitlab.com') !== false) {
                                                echo '<i class="fab fa-gitlab fa-2x text-orange"></i>';
                                            } elseif (strpos($url, 'bitbucket.org') !== false) {
                                                echo '<i class="fab fa-bitbucket fa-2x text-primary"></i>';
                                            } else {
                                                echo '<i class="fas fa-code fa-2x text-primary"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1">
                                                <?php echo htmlspecialchars($link['title']); ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($link['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if ($link['description']): ?>
                                        <p class="card-text flex-grow-1">
                                            <?php echo htmlspecialchars($link['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="btn btn-primary w-100">
                                            <i class="fas fa-external-link-alt me-2"></i>
                                            Перейти к коду
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-4">
                    <div class="alert alert-light">
                        <i class="fas fa-heart text-danger"></i>
                        <strong>Вклад в проект</strong>
                        <p class="mb-0 mt-2">
                            Если вы нашли ошибку или хотите предложить улучшение, 
                            создайте issue или pull request в соответствующем репозитории.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
.link-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,.125);
}
.link-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.link-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background-color: rgba(0,0,0,0.05);
}
.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.3;
}
.card-text {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}
.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    font-weight: 500;
}
.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
    transform: translateY(-1px);
}
[data-theme="dark"] .link-card {
    background-color: #2c3034;
    border-color: #495057;
    color: #e9ecef;
}
[data-theme="dark"] .link-icon {
    background-color: rgba(255,255,255,0.1);
}
[data-theme="dark"] .card-text {
    color: #adb5bd;
}
[data-theme="dark"] .alert-light {
    background-color: #2c3034;
    border-color: #495057;
    color: #e9ecef;
}
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
    }
    .link-card {
        margin-bottom: 0;
    }
}
</style>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layout.php';
?> 