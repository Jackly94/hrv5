<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
            Route::get('/login')
                ->action(Web\Login\Action::class)
                ->name('login'),
            Route::get('/dashboard')
                ->action(Web\Dashboard\Action::class)
                ->name('dashboard'),
            Route::get('/dashboard/index')
                ->action(Web\Dashboard\Action::class)
                ->name('dashboard-index'),
            Route::get('/menu')
                ->action(Web\Menu\Action::class)
                ->name('menu'),
            Route::get('/lantikan/index')
                ->action(Web\Lantikan\IndexAction::class)
                ->name('lantikan-index'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-staf-baru')
                ->action(Web\Lantikan\FormAction::class)
                ->name('lantikan-lantik-staf-baru'),
            Route::get('/lantikan/lantik-staf-baru/result')
                ->action(Web\Lantikan\ResultAction::class)
                ->name('lantikan-lantik-staf-baru-result'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-phs')
                ->action(Web\Lantikan\FormAction::class)
                ->name('lantikan-lantik-phs'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-khas')
                ->action(Web\Lantikan\FormAction::class)
                ->name('lantikan-lantik-khas'),
            Route::methods(['GET', 'POST'], '/lantikan/lantik-spdp')
                ->action(Web\Lantikan\FormAction::class)
                ->name('lantikan-lantik-spdp'),
            Route::get('/lantikan/lssu-index')
                ->action(Web\Lantikan\SearchAction::class)
                ->name('lantikan-lssu-index'),
            Route::get('/lantikan/lantikan-belum-selesai')
                ->action(Web\Lantikan\IncompleteAction::class)
                ->name('lantikan-lantikan-belum-selesai'),
            Route::get('/lantikan/view-utama')
                ->action(Web\Lantikan\VerificationAction::class)
                ->name('lantikan-view-utama'),
            Route::get('/lantikan/view-lantikan')
                ->action(Web\Lantikan\VerificationAction::class)
                ->name('lantikan-view-lantikan'),
            Route::get('/lantikan/tkpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-tkpg'),
            Route::get('/lantikan/lpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lpg'),
            Route::get('/lantikan/tklpg')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-tklpg'),
            Route::get('/lantikan/lantikan-lpu')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantikan-lpu'),
            Route::get('/lantikan/lantik-pli')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantik-pli'),
            Route::get('/lantikan/lantik-sambilanakademik')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantik-sambilanakademik'),
            Route::get('/lantikan/penerbitums')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-penerbitums'),
            Route::get('/lantikan/lantikan-ejobs')
                ->action(Web\Lantikan\LegacyRedirectAction::class)
                ->name('lantikan-lantikan-ejobs'),
            Route::get('/kehadiran/index')
                ->action(Web\Kehadiran\IndexAction::class)
                ->name('kehadiran-index'),
            Route::get('/kehadiran/laporan-kehadiran')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-laporan-kehadiran'),
            Route::get('/kehadiran/tindakan_ketidakpatuhan')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-tindakan-ketidakpatuhan'),
            Route::get('/kehadiran/staf-color-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-staf-color-list'),
            Route::get('/kehadiran/kad-warna')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-kad-warna'),
            Route::get('/kehadiran/kad_warna')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-kad-warna-legacy-slug'),
            Route::get('/kehadiran/wfh-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-wfh-list'),
            Route::get('/kehadiran/absent-list')
                ->action(Web\Kehadiran\StaffAction::class)
                ->name('kehadiran-absent-list'),
            Route::get('/kehadiran/senarai_tindakan')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-senarai-tindakan'),
            Route::get('/kehadiran/staff-color-card')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-staff-color-card'),
            Route::get('/kehadiran/pantau-kehadiran')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-pantau-kehadiran'),
            Route::get('/kehadiran/monthly-summary')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-monthly-summary'),
            Route::get('/kehadiran/wfh-lulus')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-wfh-lulus'),
            Route::get('/kehadiran/pantau-permohonan-wfh-pegawai')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-pantau-permohonan-wfh-pegawai'),
            Route::get('/kehadiran/absent-list-kp')
                ->action(Web\Kehadiran\OfficerAction::class)
                ->name('kehadiran-absent-list-kp'),
            Route::methods(['GET', 'POST'], '/kehadiran/pengesahan')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-pengesahan'),
            Route::methods(['GET', 'POST'], '/kehadiran/wfh-lulus-tindakan')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-wfh-lulus-tindakan'),
            Route::methods(['GET', 'POST'], '/kehadiran/delete-wfh')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-delete-wfh'),
            Route::methods(['GET', 'POST'], '/kehadiran/verify-thtc-sv')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-verify-thtc-sv'),
            Route::methods(['GET', 'POST'], '/kehadiran/approve-thtc')
                ->action(Web\Kehadiran\WriteAction::class)
                ->name('kehadiran-approve-thtc'),
            Route::get('/kehadiran/senarai_kakitangan')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-senarai-kakitangan'),
            Route::get('/kehadiran/staff-wbb')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-staff-wbb'),
            Route::get('/kehadiran/sup-mth-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-mth-rpt'),
            Route::get('/kehadiran/sup-year-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-year-rpt'),
            Route::get('/kehadiran/senarai-warna-kad')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-senarai-warna-kad'),
            Route::get('/kehadiran/sup-year-performance-rpt')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-sup-year-performance-rpt'),
            Route::get('/kehadiran/pantau-staf')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-pantau-staf'),
            Route::get('/kehadiran/shield-status-list')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-shield-status-list'),
            Route::get('/kehadiran/staff-shift-list')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-staff-shift-list'),
            Route::get('/kehadiran/shift-setup')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-shift-setup'),
            Route::get('/kehadiran-admin/manage-shift-supervisor')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-admin-manage-shift-supervisor'),
            Route::get('/kehadiran/wfh-by-mth')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-wfh-by-mth'),
            Route::get('/kehadiran/wfh-jadual')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-wfh-jadual'),
            Route::get('/kehadiran/pantau-permohonan-wfh')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-pantau-permohonan-wfh'),
            Route::get('/kehadiran/absent-list-sv')
                ->action(Web\Kehadiran\SupervisorAction::class)
                ->name('kehadiran-absent-list-sv'),
            Route::get('/kehadiran/kj-pantau-kehadiran')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-kj-pantau-kehadiran'),
            Route::get('/kehadiran/wfh-jadual-kj')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-wfh-jadual-kj'),
            Route::get('/kehadiran/list-kj')
                ->action(Web\Kehadiran\HeadDepartmentAction::class)
                ->name('kehadiran-list-kj'),
            Route::get('/kehadiran/staff-all')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-staff-all'),
            Route::get('/kehadiran/admin-mth-rpt')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-admin-mth-rpt'),
            Route::get('/kehadiran/color-rpt')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-color-rpt'),
            Route::get('/attendance/index')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('attendance-index'),
            Route::get('/kehadiran/pantau-permohonan-wfh-admin')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-pantau-permohonan-wfh-admin'),
            Route::get('/kehadiran/wfh-jadual-admin')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-wfh-jadual-admin'),
            Route::get('/kehadiran/statistik-wfh')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-statistik-wfh'),
            Route::get('/kehadiran/admin-post-list')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('kehadiran-admin-post-list'),
            Route::get('/attendance/absent-list-bpsm')
                ->action(Web\Kehadiran\AdminAction::class)
                ->name('attendance-absent-list-bpsm'),
            Route::get('/cuti/individu/index')
                ->action(Web\Cuti\Individu\IndexAction::class)
                ->name('cuti-individu-index'),
            Route::get('/cuti/individu/jsoncalendar')
                ->action(Web\Cuti\Individu\JsonCalendarAction::class)
                ->name('cuti-individu-jsoncalendar'),
            Route::get('/cuti/individu/list')
                ->action(Web\Cuti\Individu\ListAction::class)
                ->name('cuti-individu-list'),
            Route::get('/cuti/individu/leave-detail')
                ->action(Web\Cuti\Individu\DetailAction::class)
                ->name('cuti-individu-leave-detail'),
            Route::get('/cuti/individu/pilih')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-pilih'),
            Route::get('/cuti/individu/ganti')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-ganti'),
            Route::get('/cuti/individu/substitute-list')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-substitute-list'),
            Route::get('/cuti/individu/statement')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-statement'),
            Route::get('/cuti/individu/entitlement')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-entitlement'),
            Route::get('/cuti/individu/gcr-list')
                ->action(Web\Cuti\Individu\PageAction::class)
                ->name('cuti-individu-gcr-list'),
            Route::methods(['GET', 'POST'], '/cuti/individu/apply-gcr')
                ->action(Web\Cuti\Individu\ApplyGcrAction::class)
                ->name('cuti-individu-apply-gcr'),
            Route::methods(['GET', 'POST'], '/cuti/individu/agree')
                ->action(Web\Cuti\Individu\AgreeAction::class)
                ->name('cuti-individu-agree'),
            Route::get('/cuti/individu/surat')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-surat'),
            Route::methods(['GET', 'POST'], '/cuti/individu/upload-file')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-upload-file'),
            Route::methods(['GET', 'POST'], '/cuti/individu/upload')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-upload'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cancel-leave')
                ->action(Web\Cuti\Shared\LegacyRedirectAction::class)
                ->name('cuti-individu-cancel-leave'),
            Route::methods(['GET', 'POST'], '/cuti/individu/ck')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-ck'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cb')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cb'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr1')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cr1'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr1-aca')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cr1-aca'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cr2')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cr2'),
            Route::methods(['GET', 'POST'], '/cuti/individu/ctr')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-ctr'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cs1')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cs1'),
            Route::methods(['GET', 'POST'], '/cuti/individu/cs2')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-cs2'),
            Route::methods(['GET', 'POST'], '/cuti/individu/hso')
                ->action(Web\Cuti\Individu\FormAction::class)
                ->name('cuti-individu-hso'),
            Route::get('/cuti/pegawai/senarai-peraku')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-senarai-peraku'),
            Route::get('/cuti/pegawai/senarai-lulus')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-senarai-lulus'),
            Route::get('/cuti/pegawai/gcr-list-checked')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-gcr-list-checked'),
            Route::get('/cuti/pegawai/list')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-list'),
            Route::get('/cuti/pegawai/staff-leave')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-staff-leave'),
            Route::get('/cuti/pegawai/sort')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-sort'),
            Route::get('/cuti/pegawai/bod-list')
                ->action(Web\Cuti\Pegawai\OfficerAction::class)
                ->name('cuti-pegawai-bod-list'),
            Route::get('/cuti/pegawai/report')
                ->action(Web\Cuti\Pentadbiran\HsoAction::class)
                ->name('cuti-pegawai-report'),
            Route::get('/manage/list')
                ->action(Web\Cuti\Manage\ManageAction::class)
                ->name('manage-list'),
            Route::get('/cuti/supervisor/sick-leave-list')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-sick-leave-list'),
            Route::get('/cuti/supervisor/supervised-staff')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-supervised-staff'),
            Route::get('/cuti/supervisor/leave-statement-report')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-leave-statement-report'),
            Route::get('/cuti/supervisor/leave-monitoring')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-leave-monitoring'),
            Route::get('/cuti/supervisor/calender')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-calender'),
            Route::get('/cuti/supervisor/gcr-list')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-gcr-list'),
            Route::get('/cuti/supervisor/gcr-list-checked')
                ->action(Web\Cuti\Supervisor\SupervisorAction::class)
                ->name('cuti-supervisor-gcr-list-checked'),
            Route::get('/cuti/observer/my-staff')
                ->action(Web\Cuti\Observer\ObserverAction::class)
                ->name('cuti-observer-my-staff'),
            Route::get('/cuti/admin/cr2-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-cr2-list'),
            Route::get('/cuti/admin/behalf-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-behalf-list'),
            Route::get('/cuti/admin/access')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-access'),
            Route::get('/cuti/admin/gcr-application-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-gcr-application-list'),
            Route::get('/cuti/admin/open-application')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-open-application'),
            Route::get('/cuti/admin/ph-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-ph-list'),
            Route::get('/cuti/admin/cb-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-cb-list'),
            Route::get('/cuti/admin/admin-index')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-admin-index'),
            Route::get('/cuti/admin/staff-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-staff-list'),
            Route::get('/cuti/admin/report')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-report'),
            Route::get('/cuti/admin/report-leave-list')
                ->action(Web\Cuti\Admin\AdminAction::class)
                ->name('cuti-admin-report-leave-list'),
            Route::get('/biodata/index')
                ->action(Web\Biodata\IndexAction::class)
                ->name('biodata-index'),
            Route::get('/biodata/userview')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-userview'),
            Route::get('/biodata/adminview')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-adminview'),
            Route::get('/biodata/lihatbiodata')
                ->action(Web\Biodata\DetailAction::class)
                ->name('biodata-lihatbiodata'),
            Route::get('/biodata/lihatbiodatakakitangan')
                ->action(Web\Biodata\DetailAction::class)
                ->name('biodata-lihatbiodatakakitangan'),
            Route::get('/biodata/ketua-program-view')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-ketua-program-view'),
            Route::get('/biodata/view-a-r-p')
                ->action(Web\Biodata\ProfileAction::class)
                ->name('biodata-view-a-r-p'),
            Route::get('/biodata/penetapanpengguna')
                ->action(Web\Biodata\SettingsAction::class)
                ->name('biodata-penetapanpengguna'),
            Route::get('/badan-profesional/view')
                ->action(Web\BadanProfesional\ViewAction::class)
                ->name('badan-profesional-view'),
            Route::methods(['GET', 'POST'], '/badan-profesional/tambahbadanprofesional')
                ->action(Web\BadanProfesional\AddAction::class)
                ->name('badan-profesional-tambahbadanprofesional'),
            Route::get('/badan-profesional/lihatbadanprofesional')
                ->action(Web\BadanProfesional\DetailAction::class)
                ->name('badan-profesional-lihatbadanprofesional'),
            Route::methods(['GET', 'POST'], '/badan-profesional/update')
                ->action(Web\BadanProfesional\AddAction::class)
                ->name('badan-profesional-update'),
            Route::post('/badan-profesional/delete')
                ->action(Web\BadanProfesional\DeleteAction::class)
                ->name('badan-profesional-delete'),
            Route::get('/alamat/view')
                ->action(Web\Alamat\ViewAction::class)
                ->name('alamat-view'),
            Route::get('/alamat/lihatalamat')
                ->action(Web\Alamat\DetailAction::class)
                ->name('alamat-lihatalamat'),
            Route::methods(['GET', 'POST'], '/alamat/tambahalamat')
                ->action(Web\Alamat\FormAction::class)
                ->name('alamat-tambahalamat'),
            Route::methods(['GET', 'POST'], '/alamat/update')
                ->action(Web\Alamat\FormAction::class)
                ->name('alamat-update'),
            Route::post('/alamat/delete')
                ->action(Web\Alamat\DeleteAction::class)
                ->name('alamat-delete'),
            Route::methods(['GET', 'POST'], '/alamat/statelist')
                ->action(Web\Alamat\JsonListAction::class)
                ->name('alamat-statelist'),
            Route::methods(['GET', 'POST'], '/alamat/citylist')
                ->action(Web\Alamat\JsonListAction::class)
                ->name('alamat-citylist'),
            Route::get('/pendidikan/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('pendidikan-view'),
            Route::get('/pendidikan/lihatpendidikan')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('pendidikan-lihatpendidikan'),
            Route::methods(['GET', 'POST'], '/pendidikan/tambahpendidikan')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pendidikan-tambahpendidikan'),
            Route::methods(['GET', 'POST'], '/pendidikan/update')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pendidikan-update'),
            Route::post('/pendidikan/delete')
                ->action(Web\StaffRecordOverviewCrud\DeleteAction::class)
                ->name('pendidikan-delete'),
            Route::get('/pasport-permit/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('pasport-permit-view'),
            Route::get('/pasport-permit/lihatpasport')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('pasport-permit-lihatpasport'),
            Route::get('/pasport-permit/lihatpermitkerja')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('pasport-permit-lihatpermitkerja'),
            Route::methods(['GET', 'POST'], '/pasport-permit/tambahpasport')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pasport-permit-tambahpasport'),
            Route::methods(['GET', 'POST'], '/pasport-permit/tambahpermitkerja')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pasport-permit-tambahpermitkerja'),
            Route::methods(['GET', 'POST'], '/pasport-permit/updatepasport')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pasport-permit-updatepasport'),
            Route::methods(['GET', 'POST'], '/pasport-permit/updatepermitkerja')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('pasport-permit-updatepermitkerja'),
            Route::post('/pasport-permit/deletepaspot')
                ->action(Web\StaffRecordOverviewCrud\DeleteAction::class)
                ->name('pasport-permit-deletepaspot'),
            Route::post('/pasport-permit/deletepermit')
                ->action(Web\StaffRecordOverviewCrud\DeleteAction::class)
                ->name('pasport-permit-deletepermit'),
            Route::get('/lesen/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('lesen-view'),
            Route::get('/lesen/lihatlesen')
                ->action(Web\Lesen\DetailAction::class)
                ->name('lesen-lihatlesen'),
            Route::methods(['GET', 'POST'], '/lesen/tambahlesen')
                ->action(Web\Lesen\FormAction::class)
                ->name('lesen-tambahlesen'),
            Route::methods(['GET', 'POST'], '/lesen/update')
                ->action(Web\Lesen\FormAction::class)
                ->name('lesen-update'),
            Route::get('/akaun/senarai-akaun')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('akaun-senarai-akaun'),
            Route::get('/akaun/lihat')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('akaun-lihat'),
            Route::methods(['GET', 'POST'], '/akaun/permohonan-penukaran-akaun-gaji')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('akaun-permohonan-penukaran-akaun-gaji'),
            Route::get('/kecacatan/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('kecacatan-view'),
            Route::get('/kecacatan/lihatkecacatan')
                ->action(Web\Kecacatan\DetailAction::class)
                ->name('kecacatan-lihatkecacatan'),
            Route::methods(['GET', 'POST'], '/kecacatan/tambahkecacatan')
                ->action(Web\Kecacatan\FormAction::class)
                ->name('kecacatan-tambahkecacatan'),
            Route::methods(['GET', 'POST'], '/kecacatan/update')
                ->action(Web\Kecacatan\FormAction::class)
                ->name('kecacatan-update'),
            Route::post('/kecacatan/delete')
                ->action(Web\Kecacatan\DeleteAction::class)
                ->name('kecacatan-delete'),
            Route::get('/keluarga/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('keluarga-view'),
            Route::get('/keluarga/lihatkeluarga')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('keluarga-lihatkeluarga'),
            Route::methods(['GET', 'POST'], '/keluarga/ums-nonums')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('keluarga-ums-nonums'),
            Route::methods(['GET', 'POST'], '/keluarga/update')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('keluarga-update'),
            Route::post('/keluarga/delete')
                ->action(Web\StaffRecordOverviewCrud\DeleteAction::class)
                ->name('keluarga-delete'),
            Route::get('/bidangkepakaran/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('bidangkepakaran-view'),
            Route::get('/bidangkepakaran/lihatbidangkepakaran')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('bidangkepakaran-lihatbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/bidangkepakaran/tambahbidangkepakaran')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('bidangkepakaran-tambahbidangkepakaran'),
            Route::methods(['GET', 'POST'], '/bidangkepakaran/update')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('bidangkepakaran-update'),
            Route::post('/bidangkepakaran/delete')
                ->action(Web\StaffRecordOverviewCrud\DeleteAction::class)
                ->name('bidangkepakaran-delete'),
            Route::get('/vaksinasi/index')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('vaksinasi-index'),
            Route::methods(['GET', 'POST'], '/vaksinasi/view-st-vaksinasi')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('vaksinasi-view-st-vaksinasi'),
            Route::methods(['GET', 'POST'], '/vaksinasi/update-status-vaksinasi')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('vaksinasi-update-status-vaksinasi'),
            Route::methods(['GET', 'POST'], '/vaksinasi/update-st-vaksinasi')
                ->action(Web\StaffRecordOverviewCrud\FormAction::class)
                ->name('vaksinasi-update-st-vaksinasi'),
            Route::get('/kelayakan-profesional/view')
                ->action(Web\StaffRecordOverview\ViewAction::class)
                ->name('kelayakan-profesional-view'),
            Route::get('/kelayakan-profesional/view-cc')
                ->action(Web\StaffRecordOverview\DetailAction::class)
                ->name('kelayakan-profesional-view-cc'),
            Route::methods(['GET', 'POST'], '/kelayakan-profesional/add-cl-cert')
                ->action(Web\KelayakanProfesional\FormAction::class)
                ->name('kelayakan-profesional-add-cl-cert'),
            Route::methods(['GET', 'POST'], '/kelayakan-profesional/update-cc')
                ->action(Web\KelayakanProfesional\FormAction::class)
                ->name('kelayakan-profesional-update-cc'),
            Route::post('/kelayakan-profesional/delete-cc')
                ->action(Web\KelayakanProfesional\DeleteAction::class)
                ->name('kelayakan-profesional-delete-cc'),
            Route::get('/bahasa/view')
                ->action(Web\StaffRecord\ViewAction::class)
                ->name('bahasa-view'),
            Route::get('/bahasa/lihatbahasa')
                ->action(Web\StaffRecord\DetailAction::class)
                ->name('bahasa-lihatbahasa'),
            Route::methods(['GET', 'POST'], '/bahasa/tambahbahasa')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('bahasa-tambahbahasa'),
            Route::methods(['GET', 'POST'], '/bahasa/update')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('bahasa-update'),
            Route::post('/bahasa/delete')
                ->action(Web\StaffRecord\DeleteAction::class)
                ->name('bahasa-delete'),
            Route::get('/anugerah/view')
                ->action(Web\StaffRecord\ViewAction::class)
                ->name('anugerah-view'),
            Route::get('/anugerah/lihatanugerah')
                ->action(Web\StaffRecord\DetailAction::class)
                ->name('anugerah-lihatanugerah'),
            Route::methods(['GET', 'POST'], '/anugerah/tambahanugerah')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('anugerah-tambahanugerah'),
            Route::methods(['GET', 'POST'], '/anugerah/update')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('anugerah-update'),
            Route::post('/anugerah/delete')
                ->action(Web\StaffRecord\DeleteAction::class)
                ->name('anugerah-delete'),
            Route::get('/pengalamankerja/view')
                ->action(Web\StaffRecord\ViewAction::class)
                ->name('pengalamankerja-view'),
            Route::get('/pengalamankerja/lihatpengalamankerja')
                ->action(Web\StaffRecord\DetailAction::class)
                ->name('pengalamankerja-lihatpengalamankerja'),
            Route::methods(['GET', 'POST'], '/pengalamankerja/tambahpengalamankerja')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('pengalamankerja-tambahpengalamankerja'),
            Route::methods(['GET', 'POST'], '/pengalamankerja/update')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('pengalamankerja-update'),
            Route::post('/pengalamankerja/delete')
                ->action(Web\StaffRecord\DeleteAction::class)
                ->name('pengalamankerja-delete'),
            Route::get('/sejarahperubatan/view')
                ->action(Web\StaffRecord\ViewAction::class)
                ->name('sejarahperubatan-view'),
            Route::get('/sejarahperubatan/lihatsejarahperubatan')
                ->action(Web\StaffRecord\DetailAction::class)
                ->name('sejarahperubatan-lihatsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/sejarahperubatan/tambahsejarahperubatan')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('sejarahperubatan-tambahsejarahperubatan'),
            Route::methods(['GET', 'POST'], '/sejarahperubatan/update')
                ->action(Web\StaffRecord\FormAction::class)
                ->name('sejarahperubatan-update'),
            Route::post('/sejarahperubatan/delete')
                ->action(Web\StaffRecord\DeleteAction::class)
                ->name('sejarahperubatan-delete'),
            Route::methods(['GET', 'POST'], '/biodata/citylist')
                ->action(Web\Biodata\JsonListAction::class)
                ->name('biodata-citylist'),
            Route::methods(['GET', 'POST'], '/biodata/peringkatcapaiankedua')
                ->action(Web\Biodata\JsonListAction::class)
                ->name('biodata-peringkatcapaiankedua'),
            Route::methods(['GET', 'POST'], '/biodata/generate')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-generate'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinipenetapanpengguna')
                ->action(Web\RecordAction\Action::class)
                ->name('biodata-kemaskinipenetapanpengguna'),
            Route::methods(['GET', 'POST'], '/gambar-profile/semakan')
                ->action(Web\RecordAction\Action::class)
                ->name('gambar-profile-semakan'),
            Route::methods(['GET', 'POST'], '/biodata/resetpassword')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-resetpassword'),
            Route::methods(['GET', 'POST'], '/biodata/change-my-password')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-change-my-password'),
            Route::methods(['GET', 'POST'], '/biodata/logout')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-logout'),
            Route::methods(['GET', 'POST'], '/biodata/admin-carian-staf')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-admin-carian-staf'),
            Route::methods(['GET', 'POST'], '/biodata/confirmtosave')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-confirmtosave'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinikakitangan')
                ->action(Web\Biodata\FormAction::class)
                ->name('biodata-kemaskinikakitangan'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskini')
                ->action(Web\Biodata\FormAction::class)
                ->name('biodata-kemaskini'),
            Route::methods(['GET', 'POST'], '/biodata/padamkakitangan')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-padamkakitangan'),
            Route::methods(['GET', 'POST'], '/biodata/kemaskinikakitangan-kprogram')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-kemaskinikakitangan-kprogram'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-ad')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-ad'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp-non-admin')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp-non-admin'),
            Route::methods(['GET', 'POST'], '/biodata/sync-to-grp-all')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-sync-to-grp-all'),
            Route::methods(['GET', 'POST'], '/biodata/grp-inactivate-staff')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-grp-inactivate-staff'),
            Route::methods(['GET', 'POST'], '/biodata/recreate-ad')
                ->action(Web\Biodata\LegacyRedirectAction::class)
                ->name('biodata-recreate-ad'),
            Route::methods(['GET', 'POST'], '/external-login')
                ->action(Web\ExternalLogin\Action::class)
                ->name('external-login'),
        ),
];
