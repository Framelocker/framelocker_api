NoVp API Docs
========

 # | Method        | TYPE | Attributues                                                    |
---|---------------| -----|----------------------------------------------------------------|
 1 | upload_file   | POST | {method:upload_file, token}                                    | 
 2 | get_files     | GET  | {method:upload_file, token}                                    |
 3 | register      | POST | {method:"register", params: {username, password, name, email}} |
 4 | signin        | POST | {method:"signin", params:{login, pass}}                        |
 5 | signout       | POST | {method:"signout", token}                                      |
 6 | upload_avatar | POST | {method:"upload_avatar", token, novp_file}                     |            
 7 | set_name      | POST | {method:"set_name", token, params: {fstname, lstname}}           |    
 
