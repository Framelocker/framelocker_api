Framelocker API Docs
========

<h3>Navigation</h3>

1. [Server API](#server-api)
2. [NodeJS Chat API](/docs/chat_invitation.md)
3. [WOD Chat specialities](#wod-chat-specialties)
5. [Chat statistic](#chat-statistics)

#Server API

 > <h5>API admin page(<b>DEVELOPMENT</b>) - http://54.148.247.25/app/novp/</h4>

 > <h5>API access(<b>DEVELOPMENT</b>) - http://54.148.247.25/app/api</h4>


 > <h5>API admin page(<b>PRODUCTION</b>) - http://app.framelocker.com/app/novp/</h4>

 > <h5>API access(<b>PRODUCTION</b>) - http://app.framelocker.com/app/api</h4>

<h4>Table 1.1 - API methods</h4>

 # | Method        		  | TYPE | Request                                                        | Response                              |
---|----------------------|------|----------------------------------------------------------------|---------------------------------------|
 1 | upload_file  		  | POST | {method:upload_file, token, novp_file,[title],[description],   |                                       |
   |					  |      |  response_host]}   											  | {status, description, [title, src]}   |
 2 | get_files   		  | GET  | {method:get_files, token}                                      | {status, description,                 |
   |			 		  |	     |  														 	  | [file_data:{id,user_id,bucket_id,     |
   |             		  |      |                                                        		  | filename,size,ext,aws,date}]}         |
 3 | register      		  | POST | {method:"register", params: {username, password, [novp_file],  |                                       |
   |					  |		 |	[response_host]	}}                                            | {status, description}                 |     
 4 | signin      		  | POST | {method:"signin", params:{login, pass}}                        | {status, description, <b>token</b>}   |
 5 | signout      		  | POST | {method:"signout", token}                                      | {status, description}                 |         
 6 | upload_avatar 		  | POST | {method:"upload_avatar", token, novp_file}                     | {status, description, [filename]}     |          
 7 | set_name      		  | POST | {method:"set_name", token, params: {fstname, lstname}}         | {status, description}                 |      
 8 | add_user             | POST | {method:"add_user", token, params: {name,role=7,email,message}}| {status, description}                 |      
 9 | assigning_to_facebook| POST | {method:"assigning_to_facebook",token,                         |                                       |   
   |                      |      |          params:{fid,access_token}}                            | {status, description}                 |
10 | facebook_signin      | POST | {method:"facebook_signin", params:{fid, access_token}}         | {status, description, user_data}      |
11 | prime_check          | GET  | {method:"prime_check", token}                                  | {status, description}                 |
12 | get_user_info        | POST | {method:"get_user_info", token, params:{user_id}}              | {status,description,user_data:{id..}} |
13 | add_contact          | POST | {method:"add_contact",token,params:{user_id or email,message}} | {status,description}                  |
14 | register_device      | POST | {method:"register_device", token, params:{device_signature}}   | {status,description}                  |
15 | get_contact_requests | POST | {method:"get_contact_requests", token}                         | {status,description,contact_requests: |
   |  		              |      |                                                                |   [{request_id,user_id,message}]      |      
16 |approve_contact_req...| POST | {method:"approve_contact_request", token, params:{request_id,  | {status,description}                  |
   |  		              |      |      accept:('true' or 'false')}}                              |                                       |      
17 | post_status          | POST | {method:"post_status", token, params:{status,message,          | {status,description}                  |
   |  		              |      |                                       attachments}}            |                                       |      
18 | get_statuses_list    | POST | {method:"get_statuses_list", token, params:{limit, offset}}    | {status,description,statuses:         |
   |  		              |      |                                                                |  [{id,uid,status,message,attachments}]|      
   |  		              |      |    <h3>For WOD chat</h3>                                       |                                       | 
1  | get_boxes		      | GET  | {method:"get_boxes", token}                                    | {status, description, boxes}          |
2  | assign_box    		  | POST | {method:"assign_box", token, params: {uid, box}}               | {status, description}                 |
3  | wc_post_status  	  | POST | {method:"wc_post_status", token, params: {message,status_icon, | {status, status_id}                   |
   |  		              |      | contact_list}, attachments}                                    |                                       |
4  | wc_get_status_feed   | POST | {method:"wc_get_status_feed", token}                           | {status, statuses}                    |
5  | addBox               | POST | {method:"addBox", token, params:{name,latitude,longitude}}     | {status, statuses}                    |
 __|______________________|______|________________________________________________________________|_______________________________________|
*  | edit_file            | POST | {method:"edit_file", token, params:{fid, title, description}}  | {status, description}                 |
*  | get_user_list        | GET  | {method:"get_user_list", token, params:{[limit], [offset]}}    | {status, description, users}          |
 
 <h4>Method details</h4>
 
 > 8) Role - integer. Value "7" means teacher's role
 
 > '[]' - Optional parameters
 
 > 9) access_token - Facebook Access Token from authorization process
 
 > 3) Register method requires Username and Password with restrictions on the number of characters - (5-200)
 
<h4>Example of usage</h4>

> We need to know about user's files

<h4>Steps:</h4>

1. Authorization
2. Get user's files

1. Sending POST request to http://api.framelocker.com/app/api with data:{method:"signin", params:{"Alexey", "1234"}}
2. Catching response from API and obtaining <b>token</b>
3. Using token for method [get_files] - http://api.framelocker.com/app/api?method=get_files&token=91c26f0fec6f834d928fcc644ef8532849803f77. We'll receive json with status(1-ok,0-error,...), description(Text for human) and json array with file's info

========

<h4>Small sample of code</h4>

	```javascript
	
	$(function(){
		$.ajax({
			type: "POST",
			url: "http://api.framelocker.com/app/api",
			dataType: "json",
			data: { method:"signin", params:{login:"alexey@oxford.com", pass:"mypass123"}},
			success: function(data){
				// Using data
			}
		});
	});
	
	```
	
<h4>Sample of uploading file with custom title and description</h4>

	```html
	
	<form enctype="multipart/form-data" method="post" id="formaFile">
		<input type="file" name="novp_file" size="20" />
		<input type="hidden" name="method" value="upload_file">
		<input type="hidden" name="title" value="Holidays">
		<input type="hidden" name="description" value="My amazing holidays">
		<input type="hidden" name="token" value="ed0a8b8c12bea7d4a64b9afb38332646457fd693">
		<input type="submit" value="upload_file">
	</form>		
	
	```
	
	```javascript
	
	$("#formaFile").submit(function(e) {
			var formData = new FormData($("#formaFile")[0]);
			$.ajax({
				type: "POST",
				url: "http://api.framelocker.com/app/api",
				processData: false,
  				contentType: false,
				data: formData,				
				success: function(data){
					console.log(data); // Check response data
				}
		});
		e.preventDefault();			
	});
	
	```
	
<h3>WOD Chat specialities</h3>

1. For Wod chat box(room) you should specified additional prefix(<b>"box_"</b>)

	```javascript
	
	...
	var room = "box_" + box_id;
	...
	
	```
	
	> <i>box_id</i> - it's just WOD chat room

<h3>Chat Statistics</h3>

1. Getting online info in specific "room" (<b>get_room_users</b>):

	```javascript
	
	...
	socket.emit('get_room_users', {room: "Room name"});
	...
	
	```
	
2. Catching response of JSON users data in <b>"notification"</b> listener specified by <i>"request_type = users_in_room"</i>
	
	```javascript
	
	...
	else if(data.request_type == 'users_in_room'){
		var users = data.users; 
	}
	...
	
	```
	
	> Object "data.users" contains users.
	> We can count users in specific room or getting other info.
	
3. Also developers have access for knowing total amount of messages in specific room (<b>count_room_records</b>):

	```javascript
	
	...
	socket.emit('count_room_records', {room:room});
	...
	
	```
	
	> Getting response from <b>"notification"</b> listener in defined by <i>"request_type=room_records"</i>
	> Amount of messages we can find in <b>data.count</b>