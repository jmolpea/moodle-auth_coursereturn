<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Language strings.
 *
 * @package    auth_coursereturn
 * @category   string
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alreadyconfirmed_login'] = 'Sua conta já está confirmada. Faça login para continuar no seu curso.';
$string['auth_coursereturndescription'] = 'Autocadastro por email que leva os novos usuários de volta ao curso de origem depois que confirmam a conta.';
$string['autoenrol'] = 'Inscrever automaticamente quando permitido';
$string['autoenrol_desc'] = 'Após a confirmação do email e as verificações de acesso exigidas, inscreve o usuário somente se um método de autoinscrição sem senha nem chave de grupo permitir. Inscrições existentes nunca são reativadas. Caso contrário, mostra a página normal de acesso ao curso.';
$string['check'] = 'Consultar sem alterar';
$string['cleanup'] = 'Remover destinos de curso expirados';
$string['confirmaccount'] = 'Confirmar minha conta';
$string['confirmandgo'] = 'Confirmar e ir para o curso';
$string['confirmationexpired'] = 'Este link de confirmação expirou e não pode mais ser usado. Entre em contato com o suporte do site para ativar sua conta.';
$string['confirmationttl'] = 'Validade do link de confirmação';
$string['confirmationttl_desc'] = 'Idade máxima desde a criação da conta. O Moodle pode excluir antes as contas não confirmadas, conforme as configurações do site. Padrão: 7 dias. Reenviar não prorroga a validade.';
$string['confirmpage_account'] = 'Conta: {$a}';
$string['confirmpage_course'] = 'Confirme para ativar sua conta. Você entrará automaticamente e será levado ao curso {$a}.';
$string['confirmpage_enrol'] = 'Ao confirmar, você ativa sua conta e solicita a inscrição no curso {$a}. Você entrará automaticamente e será levado ao curso.';
$string['confirmpage_ignore'] = 'Se você não solicitou esta conta, feche esta página: nada será ativado.';
$string['confirmpage_nocourse'] = 'Confirme para ativar sua conta. Em seguida, você entrará automaticamente.';
$string['confirmpage_title'] = 'Confirme sua conta';
$string['description'] = 'Habilite este método de autenticação e selecione-o como método de autocadastro em Gerenciar autenticação. Envie os visitantes para /auth/coursereturn/start.php?id=IDCURSO ou /course/view.php?id=IDCURSO. As contas de email existentes não mudam. MFA, políticas e campos obrigatórios do perfil devem ser concluídos antes do acesso ao curso. Não exclua finish.php do MFA. Habilite o reCAPTCHA para limitar cadastros automatizados.';
$string['diag_activeenrol'] = 'Inscrição ativa';
$string['diag_auth'] = 'Método de autenticação';
$string['diag_autoenrol'] = 'Inscrição automática';
$string['diag_confirmed'] = 'Confirmada';
$string['diag_coursefromurl'] = 'Curso da URL original';
$string['diag_coursevisible'] = 'Curso visível para o usuário';
$string['diag_currentlogin'] = 'Acesso atual';
$string['diag_divert'] = 'Desvio de email configurado (divertallemailsto)';
$string['diag_emailfailed'] = 'Eventos de falha de email (todos os tipos)';
$string['diag_enabled'] = 'Método de autenticação habilitado';
$string['diag_enrolself'] = 'Permissão enrol/self:enrolself';
$string['diag_lastemailfailed'] = 'Último evento de falha de email';
$string['diag_lastlogin'] = 'Último acesso';
$string['diag_mfa'] = 'MFA habilitado';
$string['diag_moodle'] = 'Versão do Moodle';
$string['diag_noemailever'] = 'Todo o email desativado (noemailever)';
$string['diag_pendingcourse'] = 'Curso pendente';
$string['diag_pendingexpiry'] = 'Expiração do curso pendente';
$string['diag_policyagreed'] = 'Política do site aceita';
$string['diag_policyhandler'] = 'Gerenciador de políticas do site';
$string['diag_policyversion'] = 'Versão da política #{$a}';
$string['diag_registerauth'] = 'Método de autocadastro (registerauth)';
$string['diag_selfenabled'] = 'Plugin de autoinscrição habilitado';
$string['diag_selfinstance'] = 'Instância de autoinscrição #{$a}';
$string['diag_smtp'] = 'SMTP configurado';
$string['diag_suspendeddeleted'] = 'Suspensa / excluída';
$string['diag_timecreated'] = 'Conta criada';
$string['diag_ttl'] = 'Validade do link de confirmação (segundos)';
$string['diag_version'] = 'Versão do plugin';
$string['diag_wantsurl'] = 'Destino original salvo';
$string['diag_wwwroot'] = 'URL do site (wwwroot)';
$string['diagnostics'] = 'Diagnóstico de cadastro e retorno ao curso';
$string['diagnostics_desc'] = 'Somente administradores. Esta página não altera a conta, não confirma links e não mostra senhas nem tokens. Consulte uma conta de teste para distinguir a configuração, o curso salvo e o estado de login.';
$string['diagnostics_interpretation'] = 'Se a conta não está confirmada e não há curso original, o destino se perdeu antes do envio do email. Se está confirmada, o link já foi usado ou um administrador confirmou a conta: reutilizá-lo não faz login novamente. Os destinos temporários são removidos ao concluir o retorno, portanto sua ausência posterior não prova uma falha. Um curso pendente válido pode ser retomado com um login normal. Habilite a inscrição automática para inscrever automaticamente. Zero eventos de falha de email não prova a entrega: verifique os registros do servidor de email, a pasta de spam e o greylisting.';
$string['diagnosticsvalue'] = 'Valor';
$string['emailconfirmation_course'] = 'Olá, {$a->firstname}!

Foi solicitada uma nova conta em "{$a->sitename}" com o seu endereço de email para acessar o curso "{$a->coursename}".

Para ativá-la, abra este link e clique em "Confirmar e ir para o curso":

{$a->link}

Em seguida, você entrará automaticamente e será levado ao curso. Se o site pedir antes que você aceite as políticas ou complete o seu perfil, você chegará ao curso depois disso.

O link é válido até {$a->expires}. Se ele não abrir ao clicar, copie-o na barra de endereços do navegador.

Se você não solicitou esta conta, ignore este email e nenhuma conta será ativada.

{$a->admin}';
$string['emailconfirmation_enrol'] = 'Olá, {$a->firstname}!

Foi solicitada uma nova conta em "{$a->sitename}" com o seu endereço de email para participar do curso "{$a->coursename}".

Para ativá-la, abra este link e clique em "Confirmar e ir para o curso":

{$a->link}

Ao confirmar, você solicita a inscrição em "{$a->coursename}". Você entrará automaticamente e, se o curso permitir autoinscrição, será inscrito e levado direto ao curso. Se o curso estiver fechado ou lotado, você verá a página de inscrição. Se o site pedir antes que você aceite as políticas ou complete o seu perfil, você chegará ao curso depois disso.

O link é válido até {$a->expires}. Se ele não abrir ao clicar, copie-o na barra de endereços do navegador.

Se você não solicitou esta conta, ignore este email: você não será cadastrado nem inscrito.

{$a->admin}';
$string['emailconfirmationsubject_course'] = '{$a->sitename}: confirme sua conta para o curso {$a->coursename}';
$string['emailconfirmsent_course'] = '<p>Enviamos um email para <b>{$a->email}</b>.</p>
<p>Abra o link desse email e confirme para ativar sua conta. Você entrará automaticamente e será levado a <b>{$a->coursename}</b>.</p>
<p>Se o email não chegar em alguns minutos, verifique a pasta de spam.</p>';
$string['entrylink'] = 'Link de entrada para cadastro neste curso';
$string['locktimeout'] = 'Outra solicitação está sendo processada. Tente novamente em instantes.';
$string['otheraccount'] = 'Há outra conta conectada. Abra este link de confirmação em uma janela anônima ou saia da sessão antes.';
$string['pluginname'] = 'Cadastro por email com retorno ao curso';
$string['privacy:pending'] = 'Identificador temporário do curso e data de expiração usados para concluir um cadastro confirmado. Removido ao concluir ou, após 24 horas, pela tarefa agendada.';
$string['signupnotice'] = 'Depois de confirmar seu email e concluir as verificações de acesso exigidas, você voltará para {$a}.';
$string['signupnotice_enrol'] = 'Ao criar esta conta para acessar {$a}, você solicita a inscrição neste curso. Depois da confirmação do email e das verificações de acesso exigidas, você será inscrito automaticamente se o curso permitir autoinscrição sem chave.';
$string['usernotfound'] = 'Conta local não encontrada.';
