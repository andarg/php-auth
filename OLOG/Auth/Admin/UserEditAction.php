<?php

namespace OLOG\Auth\Admin;

use OLOG\Auth\Auth;
use OLOG\Auth\Operator;
use OLOG\Auth\Permission;
use OLOG\Auth\Permissions;
use OLOG\Auth\PermissionToUser;
use OLOG\Auth\User;
use OLOG\BT\BT;
use OLOG\BT\InterfaceBreadcrumbs;
use OLOG\BT\InterfacePageTitle;
use OLOG\BT\InterfaceUserName;
use OLOG\BT\Layout;
use OLOG\CRUD\CRUDForm;
use OLOG\CRUD\CRUDFormRow;
use OLOG\CRUD\CRUDFormWidgetInput;
use OLOG\CRUD\CRUDFormWidgetReference;
use OLOG\CRUD\CRUDTableFilter;
use OLOG\Exits;
use OLOG\Operations;
use OLOG\POSTAccess;
use OLOG\Url;

class UserEditAction
    implements InterfaceBreadcrumbs,
    InterfacePageTitle,
    InterfaceUserName
{
    use CurrentUserNameTrait;

    const OPERATION_SET_PASSWORD = 'OPERATION_SET_PASSWORD';
    const FIELD_NAME_PASSWORD = 'password';

    protected $user_id;

    public function currentBreadcrumbsArr(){
        return self::breadcrumbsArr($this->user_id);
    }

    static public function breadcrumbsArr($user_id)
    {
        return array_merge(UsersListAction::breadcrumbsArr(), [BT::a(self::getUrl($user_id), self::pageTitle($user_id))]);
    }

    public function currentPageTitle()
    {
        return self::pageTitle($this->user_id);
    }

    static public function pageTitle($user_id){
        return 'Пользователь ' . $user_id;
    }

    static public function getUrl($user_id = '(\d+)'){
        return '/admin/auth/user/' . $user_id;
    }

    public function action($user_id){
        Exits::exit403If(
            !Operator::currentOperatorHasAnyOfPermissions([Permissions::PERMISSION_PHPAUTH_MANAGE_USERS])
        );

        $this->user_id = $user_id;

        Operations::matchOperation(self::OPERATION_SET_PASSWORD, function() use ($user_id) {
            $new_password = POSTAccess::getOptionalPostValue(self::FIELD_NAME_PASSWORD);
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            $user_obj = User::factory($user_id);
            $user_obj->setPasswordHash($new_password_hash);
            $user_obj->save();
        });

        $user_obj = User::factory($user_id);

        $html = '';

        $html .= '<div class="row"><div class="col-md-6">';

        $html .= '<h2>Параметры</h2>';

        $html .= CRUDForm::html(
            $user_obj,
            [
                new CRUDFormRow(
                    'Login',
                    new CRUDFormWidgetInput('login')
                )
            ]
        );

        $html .= '<h2>Изменение пароля</h2>';
        $html .= '<form class="form-horizontal" role="form" method="post" action="' . Url::getCurrentUrl() . '">';
        $html .= Operations::operationCodeHiddenField(self::OPERATION_SET_PASSWORD);

        $html .= '<div class="form-group ">
<label class="col-sm-4 text-right control-label">Новый пароль</label>
<div class="col-sm-8">
<input name="' . self::FIELD_NAME_PASSWORD . '" class="form-control" value="">
</div>
</div>';

        $html .= '<div class="row">
<div class="col-sm-8 col-sm-offset-4">
<button style="width: 100%" type="submit" class="btn btn-primary">Сохранить</button>
</div>
</div>';

        $html .= '</form>';

        $html .= '<h2>Операторы пользователя</h2>';

        $new_operator_obj = new Operator();
        $new_operator_obj->setUserId($user_id);

        $html .= \OLOG\CRUD\CRUDTable::html(
            \OLOG\Auth\Operator::class,
            CRUDForm::html(
                $new_operator_obj,
                [
                    new CRUDFormRow(
                        'user_id',
                        new CRUDFormWidgetReference('user_id', User::class, 'login')
                    ),
                    new CRUDFormRow(
                        'title',
                        new CRUDFormWidgetInput('title')
                    )
                ]
            ),
            [
                new \OLOG\CRUD\CRUDTableColumn(
                    'Описание', new \OLOG\CRUD\CRUDTableWidgetTextWithLink('{this->title}', OperatorEditAction::getUrl('{this->id}'))
                ),
                new \OLOG\CRUD\CRUDTableColumn(
                    'Удалить', new \OLOG\CRUD\CRUDTableWidgetDelete()
                )
            ],
            [
                new CRUDTableFilter('user_id', CRUDTableFilter::FILTER_EQUAL, $user_id)
            ]
        );

        $html .= '</div><div class="col-md-6">';

        if (Operator::currentOperatorHasAnyOfPermissions([Permissions::PERMISSION_PHPAUTH_MANAGE_USERS_PERMISSIONS])) {
            $html .= '<h2>Разрешения</h2>';

            $new_permissiontouser_obj = new PermissionToUser();
            $new_permissiontouser_obj->setUserId($user_id);

            $html .= \OLOG\CRUD\CRUDTable::html(
                \OLOG\Auth\PermissionToUser::class,
                CRUDForm::html(
                    $new_permissiontouser_obj,
                    [
                        new CRUDFormRow(
                            'user_id',
                            new CRUDFormWidgetInput('user_id', false, true)
                        ),
                        new CRUDFormRow(
                            'permission',
                            new CRUDFormWidgetReference('permission_id', Permission::class, 'title')
                        )
                    ]
                ),
                [
                    new \OLOG\CRUD\CRUDTableColumn(
                        'Разрешение', new \OLOG\CRUD\CRUDTableWidgetText('{' . Permission::class . '.{this->permission_id}->title}')
                    ),
                    new \OLOG\CRUD\CRUDTableColumn(
                        'Удалить', new \OLOG\CRUD\CRUDTableWidgetDelete()
                    )
                ],
                [
                    new CRUDTableFilter('user_id', CRUDTableFilter::FILTER_EQUAL, $user_id)
                ]
            );
        }


        $html .= '</div></div>';

        Layout::render($html, $this);
    }
}