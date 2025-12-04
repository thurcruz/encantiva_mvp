<?php
// admin/sidebar.php - Barra de Navegação Lateral
// Nota: O CSS desta barra DEVE estar incluído no header.php.

// Determina a página atual para destacar o link ativo (opcional, mas profissional)
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/encantiva_logo_white.png" alt="Encantiva" class="logo-sidebar" id="sidebarLogo">
        
        <div style="padding: 10px 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
            
            <label class="dark-mode-switch-container" for="darkModeToggle">
                <input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()">
                <span class="slider round"></span>
            </label>
            <span style="color: white; font-size: 14px;">Modo Escuro</span>
        </div>
        </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="icon-dashboard"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="gestor.php" class="<?php echo ($current_page == 'gestor.php') ? 'active' : ''; ?>">
                    <i class="icon-pedidos"></i> Pedidos
                </a>
            </li>
            <li>
                <a href="temas.php" class="<?php echo ($current_page == 'temas.php') ? 'active' : ''; ?>">
                    <i class="icon-temas"></i> Temas
                </a>
            </li>
            <li>
                <a href="combos.php" class="<?php echo ($current_page == 'combos.php') ? 'active' : ''; ?>">
                    <i class="icon-combos"></i> Combos
                </a>
            </li>
            <li>
                <a href="adicionais.php" class="<?php echo ($current_page == 'adicionais.php') ? 'active' : ''; ?>">
                    <i class="icon-adicionais"></i> Adicionais
                </a>
            </li>
            <li>
                <a href="clientes.php" class="<?php echo ($current_page == 'clientes.php') ? 'active' : ''; ?>">
                    <i class="icon-clientes"></i> Clientes
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <p>Olá, <?php echo htmlspecialchars($_SESSION['admin_nome'] ?? 'Admin'); ?></p>
        <a href="../logout.php" class="btn-logout">Sair</a>
    </div>
</div>

