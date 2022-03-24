<?php

Route::group([
	'prefix' => 'api',
], function(){

	Route::post('convert_image', 'Insyghts\Hubstaff\Controllers\ActivitiesController@convert_to_image');
	// Same route for listing and filter
	Route::post('hubstaff/attendances', 'Insyghts\Hubstaff\Controllers\AttendanceController@attendances');
	
	Route::post('hubstaff/attendance/save', 'Insyghts\Hubstaff\Controllers\AttendanceController@storeAttendanceLog');
	Route::get('hubstaff/attendance/last', 'Insyghts\Hubstaff\Controllers\AttendanceController@getLastAttendance');
	
	Route::get('hubstaff/attendance/{id}', 'Insyghts\Hubstaff\Controllers\AttendanceController@showAttendance');
	Route::post('hubstaff/user-attendance', 'Insyghts\Hubstaff\Controllers\AttendanceController@getAttendanceByUserAndDate');
	Route::get('hubstaff/attendance/user/{id}', 'Insyghts\Hubstaff\Controllers\AttendanceController@getAttendanceByUser');
	Route::get('hubstaff/attendance/date/{date}', 'Insyghts\Hubstaff\Controllers\AttendanceController@getAttendanceByDate');
	
	// Same route for listing and filter
	Route::post('hubstaff/activity-logs', 'Insyghts\Hubstaff\Controllers\ActivitiesController@listActivityLog');
	Route::post('hubstaff/activity-screenshots', 'Insyghts\Hubstaff\Controllers\ActivitiesController@listActivityScreenshots');
	Route::post('hubstaff/activity-log/save', 'Insyghts\Hubstaff\Controllers\ActivitiesController@storeActivityLog');
	Route::delete('hubstaff/activity-log/delete/{id}', 'Insyghts\Hubstaff\Controllers\ActivitiesController@deleteActivityLog');

	Route::get('hubstaff/config', 'Insyghts\Hubstaff\Controllers\HubstaffConfigController@viewConfig');
	Route::post('hubstaff/config/store', 'Insyghts\Hubstaff\Controllers\HubstaffConfigController@storeConfig');
	Route::put('hubstaff/config/update/{id}', 'Insyghts\Hubstaff\Controllers\HubstaffConfigController@updateConfig');
	Route::delete('hubstaff/config/delete/{id}', 'Insyghts\Hubstaff\Controllers\HubstaffConfigController@deleteConfig');

	// Get server's timestamp
	Route::get('hubstaff/server/timestamps', 'Insyghts\Hubstaff\Controllers\HubstaffServerController@getTimestamp');
});
