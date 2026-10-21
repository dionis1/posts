@extends('layouts.app')
@section('title', __('Team'))
@section('content')
<header class="page-header"><div><p class="eyebrow">{{ __('People and permissions') }}</p><h1>{{ __('Team') }}</h1></div></header>
<p class="muted">{{ __('Authors write their own posts. Reviewers review others. Editors manage content. Administrators also manage team roles.') }}</p>
<div class="card table-wrap">
<table><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Role') }}</th><th>{{ __('Action') }}</th></tr></thead>
<tbody>
@foreach($users as $member)
<tr>
    <td>{{ $member->name }} @if($member->id === auth()->id())<span class="badge">{{ __('You') }}</span>@endif</td>
    <td>{{ $member->email }}</td>
    <td><form id="role-{{ $member->id }}" method="POST" action="{{ route('team.update', $member) }}">@csrf @method('PATCH')<label class="sr-only" for="member-{{ $member->id }}">{{ __('Role for :name', ['name' => $member->name]) }}</label><select id="member-{{ $member->id }}" name="role">@foreach($roles as $role)<option value="{{ $role->value }}" @selected($member->role === $role)>{{ __(ucfirst($role->value)) }}</option>@endforeach</select></form></td>
    <td><button class="button secondary" type="submit" form="role-{{ $member->id }}">{{ __('Save role') }}</button></td>
</tr>
@endforeach
</tbody></table>
</div>
{{ $users->links() }}
@endsection
