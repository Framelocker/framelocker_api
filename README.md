Framelocker API Docs
========

 # | Method        | TYPE | Request                                                        | Response                              |
---|---------------| -----|----------------------------------------------------------------|---------------------------------------|
 1 | upload_file   | POST | {method:upload_file, token}                                    | {status, description, [title, src]}   |
 2 | get_files     | GET  | {method:get_files, token}                                      | {status, description,                 |
   			     	    															 	     [file_data:{id,user_id,bucket_id,     |
                                                                            		         filename,size,ext,aws,date}]}         |
 3 | register      | POST | {method:"register", params: {username, password, name, email}} | {status, description}                 |     
 4 | signin        | POST | {method:"signin", params:{login, pass}}                        | {status, description, <b>token</b>}   |
 5 | signout       | POST | {method:"signout", token}                                      | {status, description}                 |         
 6 | upload_avatar | POST | {method:"upload_avatar", token, novp_file}                     | {status, description, [filename]}     |          
 7 | set_name      | POST | {method:"set_name", token, params: {fstname, lstname}}         | {status, description}                 |   
 
