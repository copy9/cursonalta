<?php
// Configurações compartilhadas da API. Não guarde senhas aqui: este arquivo vai para o GitHub.

// Conta com acesso ao painel de administração (admin.html).
// Quem tem este e-mail precisa se cadastrar normalmente no site, pelo mesmo e-mail.
const ADMIN_EMAIL = 'knigimarco@gmail.com';

function is_admin($user) {
    return is_array($user) && isset($user['email']) && $user['email'] === ADMIN_EMAIL;
}
