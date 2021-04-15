<?php

namespace GiocoPlus\Admin\Controllers;

use GiocoPlus\Admin\Widgets\Tab;
use GiocoPlus\Admin\Widgets\Box;
use GiocoPlus\Admin\Form;
use GiocoPlus\Admin\Grid;
use GiocoPlus\Admin\Tree;
use GiocoPlus\Admin\Show;
use GiocoPlus\Admin\Facades\Admin;
use GiocoPlus\Admin\Layout\Content;
use GiocoPlus\Admin\Layout\Row;
use GiocoPlus\Admin\Layout\Column;
use GiocoPlus\Admin\Controllers\ModelForm;
use GiocoPlus\Admin\Auth\Database\Administrator;
use App\Models\Company;
use App\Helper\GlobalParam;

class UserController extends AdminController
{
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    // public function index(Content $content)
    // {
    //     $content->header('管理員');
    //     $content->description('列表');
    //     $content->row(function (Row $row) {
    //         $row->column(12, $this->treeView());
    //     });

    //     return $content;
    // }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $userModel = config('admin.database.users_model');
        $roleModel = config('admin.database.roles_model');

        $grid = new Grid(new $userModel());
        $grid->column('username', trans('admin.username'));
        $grid->column('name', trans('admin.name'));
        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();
        if (!\Admin::user()->isRole('merchant'))
        {
            $grid->column('belongToCompany.name', trans('form.belongCompany'));
        }
        else;
        $grid->column('created_at', trans('admin.created_at'));
        // $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
            $actions->disableView();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        if (\Admin::user()->isRole('agent'))
        {
            $companies = Company::selectOptions(function($q){
                return $q->with('children.children.children.children.children')->where('id', \Admin::user()->company_id);
            }, null);
            $grid->model()->whereIn('company_id', array_keys($companies));
        }
        else if (\Admin::user()->isRole('merchant'))
        {
            $grid->model()->where('company_id', \Admin::user()->company_id);
        }
        else;

        $grid->filter(function ($filter) use($roleModel){
            $filter->disableIdFilter();
            $filter->like('username', trans('admin.username'));
            if (\Admin::user()->isRole('administrator'))
            {
                $filter->equal('company_id', trans('admin.belongCompany'))
                       ->select(Company::selectOptions(null,null));
            }
            else;
            if (!\Admin::user()->isRole('administrator')){
                $filter->equal('company_id', trans('admin.belongCompany'))
                       ->select(Company::selectOptions(function($q){
                            return $q->with('children.children.children.children.children')
                                     ->where('id', \Admin::user()->company_id);
                        }, null));
            }

        });
        $grid->disableCreateButton(false);
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = config('admin.database.users_model');

        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });

        $form->ignore(['password_confirmation']);

        if (\Admin::user()->isRole('administrator')) {
            $form->select('company_id', trans('form.belongCompany'))->options(Company::selectOptions(function($q){
                return $q->with('children.children.children.children.children');
            }, null));
            $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        } else {
            if (\Admin::user()->isRole('merchant')) {
                $form->hidden('company_id')->default(\Admin::user()->assign_company);
            } else {
                $form->select('company_id', trans('form.belongCompany'))->options(Company::selectOptions(function($q){
                    return $q->with('children.children.children.children.children')->where('id', \Admin::user()->belong_company);
                }, null));
                $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::where('slug', '!=', 'administrator')->pluck('name', 'id'));
            }
        }

        //$form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        // $form->display('created_at', trans('admin.created_at'));
        // $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            }
        });

        return $form;
    }

    /**
     * @return \GiocoPlus\Admin\Tree
     */
    protected function treeView()
    {
        return Administrator::tree(function (Tree $tree) {
            // $tree->disableCreate();
            $tree->branch(function ($branch) {
                // \Log::info($branch);
                // $vals = explode(',', $branch['keywords']);
                // $keywords = implode('&nbsp;', array_map(function($val){
                //     return "<span class='label label-info'>$val</span>";
                // }, $vals));
                $payload = "<i class='fa fa-bars'></i>&nbsp;<strong>{$branch['name']}</strong>&nbsp;&nbsp;<label>角色：{$branch['roles']}</label>";

                return $payload;
            });
            // ->query(function($q){
            //     return $q->where('id', \Admin::user()->id);
            // });
        });
    }
}
