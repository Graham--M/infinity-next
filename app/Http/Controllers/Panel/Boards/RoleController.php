<?php namespace App\Http\Controllers\Panel\Boards;

use App\Board;
use App\Permission;
use App\PermissionGroup;
use App\Role;
use App\RolePermission;
use App\Http\Controllers\Panel\PanelController;

use Input;

use Event;
use App\Events\RoleWasModified;

class RoleController extends PanelController {
	
	/*
	|--------------------------------------------------------------------------
	| Roles Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles an index request for all roles in the system.
	|
	*/
	
	const VIEW_PERMISSIONS = "panel.roles.permissions.edit";
	const VIEW_EDIT        = "panel.board.roles.edit";
	
	/**
	 * View path for the secondary (sidebar) navigation.
	 *
	 * @var string
	 */
	public static $navSecondary = "nav.panel.board";
	
	/**
	 * View path for the tertiary (inner) navigation.
	 *
	 * @var string
	 */
	public static $navTertiary = "nav.panel.board.settings";
	
	/**
	 * Show role basic details.
	 *
	 * @param  \App\Board  $board
	 * @return Response
	 */
	public function getIndex(Board $board)
	{
		if (!$this->user->canEditConfig($board))
		{
			return abort(403);
		}
		
		$roles   = Role::whereCanParentForBoard($board, $this->user)->get();
		$choices = [];
		
		foreach ($roles as $role)
		{
			$choices[$role->getDisplayName()] = $role->role;
		}
		
		return $this->view(static::VIEW_CREATE, [
			'board'   => $board,
			'choices' => $choices,
			'tab'     => "roles",
		]);
	}
	
	/**
	 * Update basic details for an existing role.
	 *
	 * @param  \App\Board  $board
	 * @return Response
	 */
	public function patchIndex(Board $board, Role $role)
	{
		if (!$this->user->canEditConfig($board))
		{
			return abort(403);
		}
		
		$rules = [
			'roleCaste'   => [
				"string",
				"alpha_num",
				"unique:roles,role,board_uri,{$board->board_uri}",
			],
			'roleName'    => [
				"required",
				"string",
			],
			'roleCapcode' => [
				"string",
			],
		];
		
		$validator = Validator::make(Input::all(), $rules);
		
		if ($validator->fails())
		{
			return redirect()
				->back()
				->withInput()
				->withErrors($validator->errors());
		}
		
		$role = new Role();
		$role->caste     = strtolower(Input::get('roleCaste'));
		$role->name      = Input::get('roleName');
		$role->capcode   = Input::get('capcode');
		$role->save();
		
		return $this->getIndex($board, $role);
	}
	
	/**
	 * Show the application dashboard to the user.
	 *
	 * @param  \App\Board  $board  The board we're working with.
	 * @param  \App\Role  $role  The role being modified.
	 * @return Response
	 */
	public function getPermissions(Board $board, Role $role)
	{
		if (!$role->canSetPermissions($this->user))
		{
			return abort(403);
		}
		
		$permission_groups = PermissionGroup::orderBy('display_order', 'asc')->withPermissions()->get();
		
		return $this->view(static::VIEW_PERMISSIONS, [
			'board'  => $board,
			'role'   => $role,
			'groups' => $permission_groups,
			
			'tab'    => "roles",
		]);
	}
	
	/**
	 * Commit updates to the role permissions.
	 *
	 * @param  \App\Board  $board  The board we're working with.
	 * @param  \App\Role  $role  The role being modified.
	 * @return Response
	 */
	public function patchPermissions(Board $board, Role $role)
	{
		if (!$role->canSetPermissions($this->user))
		{
			return abort(403);
		}
		
		$input           = Input::all();
		$permissions     = Permission::all();
		$rolePermissions = [];
		$nullPermissions = [];
		
		foreach ($permissions as $permission)
		{
			if ($this->user->can($permission->permission_id))
			{
				$nullPermissions[] = $permission->permission_id;
				
				foreach ($input['permission'] as $permission_id => $permission_value)
				{
					$permission_id = str_replace("_", ".", $permission_id);
					
					if ($permission->permission_id == $permission_id)
					{
						switch ($permission_value)
						{
							case "allow" :
							case "revoke"  :
							case "deny"  :
								$rolePermissions[$permission_id] = [
									'role_id'       => $role->role_id,
									'permission_id' => $permission_id,
									'value'         => $permission_value == "allow",
								];
							break;
						}
						
						break;
					}
				}
			}
		}
		
		$role->permissions()->detach($nullPermissions);
		$role->permissions()->attach($rolePermissions);
		
		$permission_groups = PermissionGroup::withPermissions()->get();
		
		Event::fire(new RoleWasModified($role));
		
		return $this->view(static::VIEW_PERMISSIONS, [
			'board'  => $board,
			'role'   => $role,
			'groups' => $permission_groups,
			
			'tab'    => "roles",
		]);
	}
}