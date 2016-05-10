<?php

namespace OLOG\Auth\Admin;

use OLOG\Auth\Operator;
use OLOG\Auth\Permission;
use OLOG\Auth\Permissions;
use OLOG\Auth\User;
use OLOG\BT;
use OLOG\BT\Layout;
use OLOG\CRUD\CRUDForm;
use OLOG\CRUD\CRUDFormRow;
use OLOG\CRUD\CRUDFormWidgetInput;
use OLOG\Exits;
use OLOG\Operations;
use OLOG\POSTAccess;
use OLOG\Url;

class UserEditAction
    implements BT\InterfaceBreadcrumbs,
    BT\InterfacePageTitle

{
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
        return 'User ' . $user_id;
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

        $html = CRUDForm::html(
            $user_obj,
            [
                new CRUDFormRow(
                    'Login',
                    new CRUDFormWidgetInput('login')
                ),
                new CRUDFormRow(
                    'Password hash',
                    new CRUDFormWidgetInput('password_hash')
                )
            ]
        );

        $html .= '<h2>Изменение пароля</h2>';
        $html .= '<div>';
        $html .= '<form method="post" action="' . Url::getCurrentUrl() . '">';
        $html .= Operations::operationCodeHiddenField(self::OPERATION_SET_PASSWORD);
        $html .= '<div>Новый пароль: <input name="' . self::FIELD_NAME_PASSWORD . '"></div>';
        $html .= '<div><input type="submit"></div>';
        $html .= '</form>';
        $html .= '</div>';

        Layout::render($html, $this);
    }
}