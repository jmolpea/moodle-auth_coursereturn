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
 * Language strings (Spanish).
 *
 * @package    auth_coursereturn
 * @category   string
 * @copyright  2026 RSMAX Consulting SL
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_coursereturndescription'] = 'Autorregistro por email que devuelve al usuario al curso de origen tras confirmar su cuenta.';
$string['autoenrol'] = 'Matricular automáticamente cuando esté permitido';
$string['autoenrol_desc'] = 'Tras confirmar el email y superar los controles de acceso, matricula solo si una instancia de automatriculación sin contraseña ni clave de grupo lo permite. Nunca reactiva matrículas existentes. En otro caso muestra el acceso normal al curso.';
$string['check'] = 'Consultar sin modificar';
$string['cleanup'] = 'Eliminar destinos de curso caducados';
$string['confirmaccount'] = 'Confirmar mi cuenta y continuar';
$string['confirmationttl'] = 'Vigencia del enlace de confirmación';
$string['confirmationttl_desc'] = 'Edad máxima desde la creación de la cuenta. Moodle puede eliminar antes las cuentas sin confirmar según sus ajustes. Predeterminado: 7 días. Reenviar no amplía la vigencia.';
$string['confirming'] = 'Confirmando su cuenta';
$string['confirming_desc'] = 'Continuará automáticamente. Si su navegador no continúa, utilice el siguiente botón.';
$string['description'] = 'Active este método de autenticación y selecciónelo como método de autorregistro en Gestionar autenticación. Dirija a los visitantes a /auth/coursereturn/start.php?id=IDCURSO o /course/view.php?id=IDCURSO. Las cuentas de email existentes se mantienen. El usuario debe completar MFA, políticas y campos obligatorios antes de acceder al curso. No excluya finish.php de MFA. Active reCAPTCHA para limitar los registros automatizados.';
$string['diag_activeenrol'] = 'Matrícula activa';
$string['diag_auth'] = 'Método de autenticación';
$string['diag_autoenrol'] = 'Matriculación automática';
$string['diag_confirmed'] = 'Confirmada';
$string['diag_coursefromurl'] = 'Curso de la URL original';
$string['diag_coursevisible'] = 'Curso visible para el usuario';
$string['diag_currentlogin'] = 'Acceso actual';
$string['diag_divert'] = 'Desvío de correo configurado (divertallemailsto)';
$string['diag_emailfailed'] = 'Eventos de email fallido (todos los tipos)';
$string['diag_enabled'] = 'Método de autenticación habilitado';
$string['diag_enrolself'] = 'Capacidad enrol/self:enrolself';
$string['diag_lastemailfailed'] = 'Último evento de email fallido';
$string['diag_lastlogin'] = 'Último acceso';
$string['diag_mfa'] = 'MFA habilitado';
$string['diag_moodle'] = 'Versión de Moodle';
$string['diag_noemailever'] = 'Todo el correo desactivado (noemailever)';
$string['diag_pendingcourse'] = 'Curso pendiente';
$string['diag_pendingexpiry'] = 'Caducidad del curso pendiente';
$string['diag_policyagreed'] = 'Política del sitio aceptada';
$string['diag_policyhandler'] = 'Gestor de políticas del sitio';
$string['diag_policyversion'] = 'Versión de política #{$a}';
$string['diag_registerauth'] = 'Método de autorregistro (registerauth)';
$string['diag_selfenabled'] = 'Plugin de automatriculación habilitado';
$string['diag_selfinstance'] = 'Instancia de automatriculación #{$a}';
$string['diag_smtp'] = 'SMTP configurado';
$string['diag_suspendeddeleted'] = 'Suspendida / eliminada';
$string['diag_timecreated'] = 'Cuenta creada';
$string['diag_ttl'] = 'Vigencia del enlace de confirmación (segundos)';
$string['diag_version'] = 'Versión del plugin';
$string['diag_wantsurl'] = 'Destino original guardado';
$string['diag_wwwroot'] = 'URL del sitio (wwwroot)';
$string['diagnostics'] = 'Diagnóstico de registro y retorno al curso';
$string['diagnostics_desc'] = 'Solo administradores. Esta página no cambia la cuenta, no confirma enlaces y no muestra contraseñas ni tokens. Consulte la cuenta de prueba para distinguir la configuración, el curso guardado y el inicio de sesión.';
$string['diagnostics_interpretation'] = 'Si la cuenta está sin confirmar y no hay curso original, el destino se perdió antes de enviar el email. Si está confirmada, el enlace ya se utilizó o un administrador confirmó la cuenta: reutilizarlo no inicia sesión otra vez. Tras completar el retorno se borran los destinos temporales, por lo que su ausencia posterior no demuestra un fallo. Un curso pendiente vigente permite retomar el destino al iniciar sesión normalmente. Active la matriculación automática para matricular automáticamente. Cero eventos de email fallido no demuestra la entrega: revise los registros del servidor de correo, la carpeta de spam y el greylisting.';
$string['diagnosticsvalue'] = 'Valor';
$string['entrylink'] = 'Enlace de entrada al registro para este curso';
$string['locktimeout'] = 'Se está procesando otra solicitud. Inténtelo de nuevo en unos instantes.';
$string['otheraccount'] = 'Hay otra cuenta con sesión iniciada. Abra este enlace de confirmación en una ventana privada o cierre primero la sesión.';
$string['pluginname'] = 'Registro por email con retorno al curso';
$string['privacy:pending'] = 'Identificador temporal del curso y fecha de caducidad para completar un registro confirmado. Se elimina al terminar o, transcurridas 24 horas, mediante la tarea programada.';
$string['signupnotice'] = 'Tras confirmar su email y completar los controles de acceso requeridos, volverá a {$a}.';
$string['signupnotice_enrol'] = 'Al crear esta cuenta para acceder a {$a}, solicita matricularse en este curso. Tras confirmar su email y completar los controles de acceso, se le matriculará automáticamente si el curso permite la automatriculación sin clave.';
$string['usernotfound'] = 'No se ha encontrado esa cuenta local.';
