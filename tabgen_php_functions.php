<?php
/* function for creating a tab */	
function create_tab($conn,$tab_name,$template_id,$createdBy,$ou_specific){
	$id = randId(26);//creating unique id
	$createAt = time()*1000;
	$query=null;
	
	$org_name=$_POST['org_name'];
	$ou_name = $_POST['ou_name'];
	$role_name = $_POST['role_name'];
	//$role_id = findRoleId($conn,$ou_name,$role_name);
	$role_id = $_POST['role_id'];
	$flag = $ou_specific == "true"?1:0;
		
	$query="INSERT INTO Tab(Id,CreateAt,UpdateAt,DeleteAt,Name,RoleName,CreatedBy,TabTemplate,RoleId,OU_Specific,
					Organisation,OrganisationUnit)
				values('$id','$createAt','$createAt',0,'$tab_name','$role_name','$createdBy','$template_id','$role_id','$flag',
					'$org_name','$ou_name')";
		
	if($role_id==null){
		echo json_encode(array("status"=>false,"message"=>"Oops! Role does not exist. Please refresh the page and try again."));
	}
	else{
		if($conn->query($query)){
			$conn->query("insert into RoleTabAsson values('$role_id','$id')");//automatically associating default tab
			echo json_encode(array("status"=>true,"message"=>"Tab created successfully"));
		}
		else{ 
			echo json_encode(array("status"=>false,"message"=>"Oops! Something is not right, try again later"));
		}
	}	
}
//to update user role and display name
function updateUserRoleAndDisplayName($userId,$con,$role,$user_displayname){
	$query="UPDATE Users SET Roles='$role',FirstName='$user_displayname' WHERE Id='$userId'";
	if($con->query($query)){
		return true;
	}
	else 
		return false;
}
//function to set either the user has access right accross all other OU or not
function userUniversalAccess($conn,$user_id,$yes_no){
	$id = randId(26);
	$query="INSERT INTO UserUniversalAccessibility(Id,UserId,UniversalAccess) values('$id','$user_id',$yes_no)";
	$conn->query($query);
}
// function to test whether the user has Universal access right
function isUserUniversalAccessRight($conn,$user_id){
	$query="select * from User_OU_Mapping where user_id='$user_id'";
	$result = $conn->query($query);
	$row = $result->fetch(PDO::FETCH_ASSOC);
	$flag = (int)$row['UniversalAccess'];
	if($flag==1)
		return true;
	else
		return false;
}
	
function randId($length){
	$id = md5(uniqid());
	$char = str_shuffle($id);
	for($i = 0, $rand = '', $l = strlen($char) - 1; $i < $length; $i ++) {
		$rand .= $char{mt_rand(0, $l)};
	}
	return $rand;
}
	/* function to find number of tabs of a particular role*/
function existingNoOfTabs($roleName,$org_unit,$conn){
	$res = $conn->query("SELECT COUNT(*) AS NO_OF_TABS 
							FROM Tab,Role 
							where Tab.RoleName='$roleName' and 
							Tab.RoleId=Role.Id and 
							Role.OrganisationUnit='$org_unit'");
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$no_of_tabs = (int)$row['NO_OF_TABS'];
	return $no_of_tabs;
}
	
//to get OU id
function findOUId($conn,$org_unit){
	$query_result = $conn->query("select Id from OrganisationUnit where OrganisationUnit='$org_unit'");
	$row_data = $query_result->fetch(PDO::FETCH_ASSOC);
	$ou_id = $row_data['Id'];
	if(isset($ou_id))
		return $ou_id;
	else return null;
}
// to get RoleId
function findRoleId($conn,$org_unit,$role_name){
	$query_result = $conn->query("select Id from Role where OrganisationUnit='$org_unit' and RoleName='$role_name'");
	$row_data = $query_result->fetch(PDO::FETCH_ASSOC);
	$role_id = $row_data['Id'];
	if(isset($role_id))
		return $role_id;
	else return null;
}

function mapUserwithOU($conn,$user_id,$ou_id,$role_id,$type){
	$query = "insert into User_OU_Mapping values('$user_id','$ou_id','$role_id','$type')";
	return($conn->query($query));
}
//to get template Id
function findTemplateId($conn,$template_name){
	$query_result = $conn->query("SELECT Id,Name FROM TabTemplate where Name='$template_name'");
	$curr_row = $query_result->fetch(PDO::FETCH_ASSOC);
	$template_id = $curr_row['Id'];
	if(isset($template_id))
		return $template_id;
	else return null;
}
//for getting template name
function getTemplateName($conn,$template_id){
	//include('connect_db.php');
	$query_res = $conn->query("select TabTemplate.Name as Template_Name from TabTemplate where id='$template_id'");
	$result_row=$query_res->fetch(PDO::FETCH_ASSOC);
	return($result_row['Template_Name']);
}
//to check if the user role in an OU is a universal or not
function isUniversalRole($conn,$role_name,$orgunit){
	$resp = $conn->query("select * from Role where RoleName='$role_name' and OrganisationUnit='$orgunit'");
	if($resp){
		$row = $resp->fetch(PDO::FETCH_ASSOC);
		$universal_role = $row['UniversalRole'];
		if($universal_role=="true")
			return true;
		else return false;
	}
	return false;
}
//to get team_name
function getTeamName($conn,$team_id){
	$result = $conn->query("select * from Teams where Id='$team_id'");
	if($result){
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$team_name = $row['Name'];
		return $team_name;
	}
	else
		return null;
}

//getting team id by username from the users table
function getTeamIdByUsername($conn,$user_name){
	$result = $conn->query("select * from Users where Username='$user_name'");
	if($result){
		$row = $result->fetch(PDO::FETCH_ASSOC);
		$team_id = $row['TeamId'];
		return $team_id;
	}
	else
		return null;
}
//function for getting parent OU Id for an organisation
function getParentOuId($conn,$ou_id){
	$query="select ParentOUId from OUHierarchy where OUId='$ou_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['ParentOUId'];
}
// function to get OU Id (which the user belong) by providing user Id
function getOuIdByUserId($conn,$user_id){
	/*$query="select Users.Id as user_id,Users.Username,Teams.Id as Team_id,Teams.Name as team_name,OrganisationUnit.Id as org_unit_id,OrganisationUnit.OrganisationUnit
			from Users,Teams,OrganisationUnit
			where Teams.Id=Users.TeamId 
			and Teams.Name=OrganisationUnit.OrganisationUnit
			and Users.Id='$user_id'";*/
	$query = "select * from User_OU_Mapping where user_id='$user_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['OU_id'];
}
//function to get user role by providing user id
function getRoleByUserId($conn,$user_id){
	$query="select Roles from Users where Id='$user_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Roles'];
}

//function to find role id by user id
function findRoleIdByUser_id($conn,$user_id){
	/*$query="select Role.Id as role_id,Roles,OrganisationUnit.OrganisationUnit
	from Users,User_OU_Mapping,OrganisationUnit,Role
	where Users.Id=User_OU_Mapping.user_id and
		OrganisationUnit.Id=User_OU_Mapping.OU_id and
		Role.RoleName=Roles and
		Role.OrganisationUnit=OrganisationUnit.OrganisationUnit and 
		Users.Id='$user_id'";*/
	$query = "select * from User_OU_Mapping where user_id='$user_id'";
	$res = $conn->query($query);
	if($res){
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row['RoleId'];
	}else{
		return null;
	}
}

//function to concate two arrays
function concate_array($arr1,$arr2){
	$res_arr = array();
	$i=0;
	for($i=0;$i<sizeof($arr1);$i++){
		$res_arr[$i]=$arr1[$i];
	}
	$j=0;
	for($j=0;$j<sizeof($arr2);$j++){
		$res_arr[($i+$j)]=$arr2[$j];
	}
	return $res_arr; 	
}

//function to find list of Teams accessible by the user by providing user id
function getTeams($conn,$user_id){
	$output=null;
	if(isUserUniversalAccessRight($conn,$user_id)){//checks whether the user is universal access right
		$query="select Teams.Name as team_name from Teams,OrganisationUnit where Teams.Name=OrganisationUnit order by team_name";
		$res = $conn->query($query);
		if($res){
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$output[]=$row;
			}
		}
	}
	else{		
		$ou_id = getOuIdByUserId($conn,$user_id);
		$my_team = getTeamByOUId($conn,$ou_id);	
		/*,array("team_name"=>$parent_team)
		$parent_ou_id=getParentOuId($conn,$ou_id);
		$parent_team =getTeamByOUId($conn,$parent_ou_id);*/	
		$output= array(array("team_name"=>$my_team));
	}
	return $output;
}

//function to find list of OUs accessible by the user by providing user id
function getOUs($conn,$user_id){
	$output=null;
	$org = getOrg_Byuser_Id($conn,$user_id);
	if(isUserUniversalAccessRight($conn,$user_id)){//checks whether the user is universal access right
		$query="select OrganisationUnit as team_name 
				from OrganisationUnit  
				where DeleteAt=0 and 
				Organisation='$org'
				order by team_name ";
		$res = $conn->query($query);
		if($res){
			$count=0;
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$output[]=$row;
				$count++;
			}
			//$output[$count]['team_name']="Associated Tabs";
		}
	}
	else{		
		$my_ou = getOU_Byuser_Id($conn,$user_id);
		$output= array(array("team_name"=>$my_ou));
		//echo "OU: ".$my_ou;
	}
	return $output;
}


//function to get OU name by providing OU id
function getOU_Byuser_Id($conn,$user_id){
	$query = "SELECT OrganisationUnit
				FROM User_OU_Mapping,OrganisationUnit
				WHERE OU_id=OrganisationUnit.Id
				and user_id='$user_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['OrganisationUnit'];
}
//function to get OU name by providing OU id
function getOrg_Byuser_Id($conn,$user_id){
	$query = "SELECT Organisation
				FROM User_OU_Mapping,OrganisationUnit
				WHERE OU_id=OrganisationUnit.Id
				and user_id='$user_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Organisation'];
}

//function to get team name by providing OU id
function getTeamByOUId($conn,$ou_id){
	$query="select Teams.Name as team_name, OrganisationUnit as org_unit_name 
			from Teams,OrganisationUnit 
			where Teams.Name=OrganisationUnit and 
				OrganisationUnit.Id='$ou_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['team_name'];
}

//function to update role type
function updateRoleType($conn,$role_id,$role_type){
	if($conn){
		$query = "Update Role set RoleType='$role_type' where Id='$role_id'";
		$conn->query($query);
	}
}

//function to get role_type using role_name
function getRoleType($conn,$role_name){
	$query = "select RoleType from Role where RoleName='$role_name'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['RoleType'];
}


//function to get users in a channel
function getUserInPrivateMessageChannel($conn,$channel_id,$my_id){
	$query = "select UserId, Username
				from ChannelMembers,Users
				where UserId=Users.Id and
				ChannelId='$channel_id' and
				UserId != '$my_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Username'];
}
//function to get name of an Organisation Unit by providing OU id
function getOUNameByOuId($conn,$ou_id){
	$query = "select * from OrganisationUnit
				where Id='$ou_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['OrganisationUnit'];
}
//function to get team id by providing OU name
function getTeamId_by_OU_name($conn,$ou_name){
	$query = "select * from Teams where Name='$ou_name'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Id'];
}

//function to get channel details by using channel name
function getChannelByName($conn,$channel_name){
	$query = "select * from Channels where DisplayName='$channel_name' and DeleteAt=0";
	$res = $conn->query($query);
	$count=0;
	while($row = $res->fetch(PDO::FETCH_ASSOC)){
		$output[]=$row;
		$count++;
	}
	if($count==1)
		return $output;
	else return null;	
}

//function to check if tab name already exists
function isTabExist($conn,$tab_name){
	$query = "select count(*) as count from Tab where Name='$tab_name' and DeleteAt=0";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	if((int)$row['count']>0){
			return true;
	}
	else{
			return false;
	}
}

//function to check if tabstrip name already exists
function isTabstripExist($conn,$tabstrip_name){
	$query = "select count(*) as count from Tabstrip where Name='$tabstrip_name' and DeleteAt=0";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	if((int)$row['count']>0){
			return true;
	}
	else{
			return false;
	}
}

//function to extract token which was stored in session at the time of login
function get_token(){
		session_start();
		$token=null;
		if(isset($_SESSION['login_header_response'])){
			$connect = new ConnectAPI();
			$header = $_SESSION['login_header_response'];
			$header_array = $connect->http_parse_headers($header);
					
			foreach ($header_array as $name => $value) {
				//echo "The value of '$name' is '$value'<br>";
				if($name=="Token"){
					$token = $value;
					break;
				}
			}
		}
		else if(isset($_COOKIE['login_header_response'])){
			$connect = new ConnectAPI();
			$header = $_COOKIE['login_header_response'];
			$header_array = $connect->http_parse_headers($header);
					
			foreach ($header_array as $name => $value) {
				//echo "The value of '$name' is '$value'<br>";
				if($name=="Token"){
					$token = $value;
					break;
				}
			}
		}
		else 
			$token=null;
											
		return $token;
}
function getUserNameById($conn,$user_id){
	$name=null;
	$query = "select * from Users where Id='$user_id'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	if($row['FirstName']!=null)
		$name=$row['FirstName'];
	else if($row['Username']!=null)
		$name=$row['Username'];
	return $name;
}

//function to check if the tab is already associated to the specific role
	function isTabAlreadyAssociated($conn,$role_id,$tab_id){
		$query = "select count(*) as count from RoleTabAsson where RoleId='$role_id' and TabId='$tab_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$count = (int)$row['count'];
		if($count>0)
			return true;
		else 
			return false;
	}
	//getting a tab with its template name
	function getTabWithTemplate($conn,$tab_id){
		$query = "SELECT Tab.*,TabTemplate.Name as Template_Name 
				FROM Tab,TabTemplate
				where Tab.TabTemplate=TabTemplate.Id and Tab.Id='$tab_id'";
		$res = $conn->query($query);
		if($res){
			$row = $res->fetch(PDO::FETCH_ASSOC);
			return $row;
		}
		else 
			return null;
	}
	
	//function to delete a tab along with channels 
	function deleteATab($conn,$tab_id){
		$tab_details = getTabWithTemplate($conn,$tab_id);
			if($tab_details['Template_Name']=="Chat Template"){
				$token_id = get_token();
				//echo json_encode(array("status"=>false,"message"=>$token_id));
				if($token_id!=null){
					/*getting channel details for the channel having same name as the earlier tab name*/
					$tab_name=$tab_details['Name'];
					$channel_details = getChannelByName($conn,$tab_name);//this returns null of the channel does not exists
					if($channel_details!=null){
						/* it means a channel already exists with the same name as tab name, so we are going to delete that channel name
						with the new tab name.	*/		
						
						$delete_channel_data = null;
											
						$delete_channel_url = "http://".IP.":8065/api/v1/channels/".$channel_details[0]['Id']."/delete";
						$deleteChannel = new ConnectAPI();
						$delete_channel_response = json_decode($deleteChannel->sendPostDataWithToken($delete_channel_url,$delete_channel_data,$token_id));
						if($deleteChannel->httpResponseCode==200){
							//it means channel has been deleted successfully
							return deleteTab($conn,$tab_id);	
						}
						else if($deleteChannel->httpResponseCode==0){
							return json_encode(array("status"=>false,"message"=>"Unable to connect API for updating channel name"));
						}
						else{
							return json_encode(array("status"=>false,"message"=>$delete_channel_response->message));
						}	
					}else{
						return json_encode(array("status"=>false,"message"=>"No channel exists with the earlier tab name"));
					}
					
				}
				else{
						return json_encode(array("status"=>false,"message"=>"Token not found. Login again."));
				}
						
			}
			else if($tab_details['Template_Name']=="Latest News Template"){
				$query = "delete from News where title=(select Name from Tab where Id='$tab_id')";
				if($conn->query($query)){
					return deleteTab($conn,$tab_id);
				}
			}else{
				//deleting Tabs which is not chat template
				return deleteTab($conn,$tab_id);
			}
		
	}
	//this function will modify tabs in Table
	function deleteTab($conn, $tab_id){
		$time = time()*1000;
		$query1 = "delete from RoleTabAsson where TabId='$tab_id'";
		$query2 = "update Tab set DeleteAt='$time' where Id='$tab_id'";
		if($conn->query($query1) && $conn->query($query2)){	
			return json_encode(array("status"=>true,"message"=>"Successfully deleted"));
		}
		else{
			return json_encode(array("status"=>false,"message"=>"Failed to delete"));
		}		
	}
	
	//function to delete an organisation unit
	
	function deleteOU($conn,$org_unit_id){
		$ou_name=getOUNameByOuId($conn,$org_unit_id);
		$time = time()*1000;
		$res1=$conn->query("update Users set DeleteAt='$time' where Id in (select user_id 
							from User_OU_Mapping where OU_id='$org_unit_id')");
		if($res1){
			$res2=$conn->query("delete from User_OU_Mapping where OU_id = '$org_unit_id'");
			if($res2){
				$query="delete from OrganisationUnit where OrganisationUnit.Id='$org_unit_id'";
				$res3 = $conn->query($query);					
				if($res3){	
					/*$conn->query("update Tab set DeleteAt='$time' 
								where RoleId in (select Id from Role where OrganisationUnit='$ou_name')");*/
					$result=$conn->query("select Id from Tab where RoleId in 
					(select Id from Role where OrganisationUnit='$ou_name')");
					if($result){	
						while($row=$result->fetch(PDO::FETCH_ASSOC)){
							deleteATab($conn,$row['Id']);
						}	
					}			
					$conn->query("Update Role set DeleteAt='$time' where OrganisationUnit='$ou_name'");
					
					$conn->query("delete from RoleTabAsson 
									where TabId in (select Id from Tab where DeleteAt!=0)
									OR RoleId in (select Id from Role where DeleteAt!=0)");
					return true;
				}
				else return false;
			}
			else{ 
				return false;
			}
		}
		else return false;
	}
	
	//function to update first name (display name)
	function updateUserFirstName($conn,$user_id,$display_name){
		$query = "update Users set FirstName='$display_name' where Id='$user_id'";
		$conn->query($query);
	}
	
	//function to get number of replies
	function getNoOfReplies($conn,$post_id){
		$query = "select count(*) as no_of_replies from Posts where RootId='$post_id'";
		$res=$conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return (int)$row['no_of_replies'];
	}
	
	//function to check whether the user has already liked the post or not
	function isAlreadyLiked($conn,$post_id,$user_id){
		$query = "select count(*) as count from Likes where post_id='$post_id' and user_id='$user_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		if((int)$row['count']>0)//check for existence of row
			return true;
		else
			return false;
	}

	//function to unlike a post
	function unlikeAPost($conn,$post_id,$user_id){
		$query = "delete from Likes where post_id='$post_id' and user_id='$user_id'";
		$res = $conn->query($query);
		return $res;
	}

	//function to like a post
	function likeAPost($conn,$post_id,$user_id){
		$query = "insert into Likes values('$post_id','$user_id')";
		$res = $conn->query($query);
		return $res;
	}
	
	//function to count the number of likes for a particular messsage
	function getNoOfLikes($conn,$post_id){
		$query = "select count(*) as count from Likes where post_id='$post_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return (int)$row['count'];
	}
	
	//function to add bookmark
	function addBookmark($conn,$post_id,$user_id){
		//check if the post has already been bookmarked or not
		if(!isAlreadyBookmarked($conn,$post_id,$user_id)){
			//in case if the post has not already been bookmarked
			//$title = $_POST['title'];//title of the bookmark
			$time = time()*1000;
			$id = randId(26);
			$query="insert into Bookmark(Id,PostId,UserId,BookmarkAt) 
					values('$id','$post_id','$user_id','$time')";
			$res = $conn->query($query);
			return $res;
		}
		else{
			return true; //in case if the post has already been bookmarked
		}
	}
	
	//function to remove bookmark
	function removeBookmark($conn,$post_id,$user_id){
		//check if the post has already been bookmarked or not
		if(isAlreadyBookmarked($conn,$post_id,$user_id)){
			//in case if the post has already been bookmarked
			$query="delete from Bookmark where PostId='$post_id' and UserId='$user_id'";
			$res = $conn->query($query);
			return $res;
		}
		else{
			return true; //in case if the post has not already been bookmarked
		}
	}
	
	//function to check whether a post has already been bookmarked by a user or not
	function isAlreadyBookmarked($conn,$post_id,$user_id){
		$query="select count(*) as count from Bookmark where PostId='$post_id' and UserId='$user_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		if((int)$row['count']>0)//check for existence of row
			return true;//in case if row exists
		else
			return false;//in case if row does not exist
	}
	//function to get a list of bookmarks
	function getBookmarks($conn,$user_id){
		$query="select Posts.* from Bookmark,Posts 
				where Posts.Id=Bookmark.PostId
				and Bookmark.UserId='$user_id' 
				order by BookmarkAt desc";
		$res = $conn->query($query);
		$output=null;
		while($row = $res->fetch(PDO::FETCH_ASSOC)){
			//$output[]=$row;
			$output->order[]=$row['Id'];
			$output->posts->$row['Id']->id=$row['Id'];
			$output->posts->$row['Id']->create_at=(double)$row['CreateAt'];
			$output->posts->$row['Id']->update_at=(double)$row['UpdateAt'];
			$output->posts->$row['Id']->delete_at=(double)$row['DeleteAt'];
			$output->posts->$row['Id']->user_id=$row['UserId'];
			$output->posts->$row['Id']->channel_id=$row['ChannelId'];
			$output->posts->$row['Id']->root_id=$row['RootId'];
			$output->posts->$row['Id']->parent_id=$row['ParentId'];
			$output->posts->$row['Id']->original_id=$row['OriginalId'];
			$output->posts->$row['Id']->message=$row['Message'];
			$output->posts->$row['Id']->type=$row['Type'];
			$output->posts->$row['Id']->props=json_decode($row['Props']);
			$output->posts->$row['Id']->hashtags=$row['Hashtags'];
			$output->posts->$row['Id']->filenames=json_decode($row['Filenames']);
			$output->posts->$row['Id']->no_of_reply=getNoOfReplies($conn,$row['Id']);
			$output->posts->$row['Id']->no_of_likes=getNoOfLikes($conn,$row['Id']);
			$output->posts->$row['Id']->isLikedByYou=isAlreadyLiked($conn,$row['Id'],$user_id);
			$output->posts->$row['Id']->isBookmarkedByYou=isAlreadyBookmarked($conn,$row['Id'],$user_id);
		}
		return json_encode($output);
	}
	
	//function to find the number of members in a particular channel
	function getMembersCount($conn,$channel_id){
		$query="select count(*) as members_count from ChannelMembers where ChannelId='$channel_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return (int)$row['members_count'];
	}
	
	//function to get channel_id by post_id from the channel table
	function getChannelIdByPost_id($conn,$post_id){
		$query="select ChannelId from Posts where Id='$post_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row['ChannelId'];
	}
	
	//function to get OU by role
	function getOUbyRole($conn,$role_id){
		$query = "select Role.Id,Role.RoleName,OrganisationUnit.OrganisationUnit as OU,Organisation
					from OrganisationUnit,Role
					where Role.OrganisationUnit=OrganisationUnit.OrganisationUnit
					and Role.Id='$role_id'";
					
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row['OU'];
	}
	
	//function to get role id by Tab id
	function getRolebyTab($conn,$tab_id){
		$query = "select RoleId from Tab where Id='$tab_id'";			
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row['RoleId'];
	}
	
	//function to check whether a tab is OU specific or not
	function isTabOUSpecific($conn,$tab_id){
		$query = "select OU_Specific from Tab where Id='$tab_id'";
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		if((int)$row['OU_Specific']==1){
			return true;
		}
		else{
			return false;
		}
	}
	
	//function to get role id by Tab id
	function getRoleNamebyId($conn,$role_id){
		$query = "select RoleName from Role where Id='$role_id'";			
		$res = $conn->query($query);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row['RoleName'];
	}
	
	//function to associate a tab to a particular role
	function associateTabToRole($conn,$role_id,$tab_id){
		$query = "insert into RoleTabAsson values('$role_id','$tab_id')";
		if($role_id!=null){
			if(!isTabAlreadyAssociated($conn,$role_id,$tab_id)){
				if($conn->query($query)){
					echo json_encode(array("status"=>true,"message"=>"Successfully associated."));
				}
				else {
					echo json_encode(array("status"=>false,"message"=>"Unable to associate, an internal problem occurs."));
				}
			}
			else{
				echo json_encode(array("status"=>false,"message"=>"Tab is already associated!"));
			}		
		}
		else 
			echo json_encode(array("status"=>false,"message"=>"Role does not exist!"));
		
	}
	
//function to get news details
function getNewsDetails($conn,$tab_name){
	$query = "select * from News where title='$tab_name'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Details'];
}

function getOrgbyOU($conn,$ou){
	$query = "select * from OrganisationUnit where OrganisationUnit='$ou'";
	$res = $conn->query($query);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	return $row['Organisation'];
}
?>
