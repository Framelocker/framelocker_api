Framelocker API Docs
========
<h4>API admin page - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/novp/</h4>
<h4>API access - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api</h4>
<h4>Table 1.1 - API methods</h4>

 # | Method        | TYPE | Request                                                        | Response                              |
---|---------------| -----|----------------------------------------------------------------|---------------------------------------|
 1 | upload_file   | POST | {method:upload_file, token}                                    | {status, description, [title, src]}   |
 2 | get_files     | GET  | {method:get_files, token}                                      | {status, description,                 |
   |			   |	  |															 	   | [file_data:{id,user_id,bucket_id,     |
   |               |      |                                                        		   | filename,size,ext,aws,date}]}         |
 3 | register      | POST | {method:"register", params: {username, password, name, email}} | {status, description}                 |     
 4 | signin        | POST | {method:"signin", params:{login, pass}}                        | {status, description, <b>token</b>}   |
 5 | signout       | POST | {method:"signout", token}                                      | {status, description}                 |         
 6 | upload_avatar | POST | {method:"upload_avatar", token, novp_file}                     | {status, description, [filename]}     |          
 7 | set_name      | POST | {method:"set_name", token, params: {fstname, lstname}}         | {status, description}                 |   
 
<h4>Example of usage</h4>
<p>We need to know about user's files</p>
<p>Steps:</p>
<ul>
   <li>Authorization</li>
   <li>Get user's files</li>
</ul>

<ul>
   <li>Sending POST request to http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api with data:{method:"signin", params:{"Alexey", "1234"}}</li>
   <li>Catching response from API and obtaining <b>token</b> <i>For now when we create a new user, his token doesn't exist and he can get token manually from admin page (Settings), clicking refresh button. And after that the api method [signin] works fine for giving back token</i></li>
   <li>Using token for method [get_files] - http://ec2-54-68-182-31.us-west-2.compute.amazonaws.com/app/api?method=get_files&token=91c26f0fec6f834d928fcc644ef8532849803f77. We'll receive json with status(1-ok,0-error,...), description(Text for human), json array with file's info</li>	
</ul>

<h4>Small sample of code</h4>
	$(function(){
		$.ajax({
			type: "POST",
			url: "<?php echo base_url('app/api'); ?>",
			dataType: "json",
			data: { method:"signin", params:{login:"alexey@oxford.com", pass:"mypass123"}},
			success: function(data){
				// Using data
			}
		});
	});
