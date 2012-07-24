<div class="content">
	<h2 id="page_title"><?php echo l('project_settings'); ?></h2>
</div>
<?php View::render('projectsettings/_nav'); ?>
<div class="content">
	<?php if (isset($errors)) { show_errors($errors); } ?>
	<form action="<?php echo Request::base($project->href("settings/members/new")); ?>" method="post" class="horizontal">
		<div class="group">
			<?php echo Form::label(l('username'), 'username'); ?>
			<?php echo Form::text('username', array('data-autocomplete' => Request::base('/_ajax/autocomplete/username'))); ?>
		</div>
		<div class="group">
			<?php echo Form::label(l('role'), 'role'); ?>
			<?php echo Form::select('role', ProjectRole::select_options()); ?>
		</div>
		<div class="group">
			<?php echo Form::submit(l('add')); ?>
		</div>
	</form>
</div>
<div>
	<form action="<?php echo Request::base($project->href("settings/members/save"));; ?>" method="post">
		<table class="list">
			<thead>
				<tr>
					<th class="fixed_name"><?php echo l('username'); ?></th>
					<th class="role"><?php echo l('role'); ?></th>
					<th class="actions"><?php echo l('actions'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($user_roles as $rel) { ?>
				<tr>
					<td><?php echo $rel->user->username; ?></td>
					<td><?php echo Form::select("role[{$rel->id}]", ProjectRole::select_options(), array('value' => $rel->project_role_id)); ?></td>
					<td><?php echo HTML::link(l('delete'), $project->href("settings/members/{$rel->user_id}/delete"), array('class' => 'button_delete', 'data-confirm' => l('confirm.remove_x', $rel->user->username))); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="actions">
			<?php echo Form::submit(l('save')); ?>
		</div>
	</form>
</div>