from troposphere import Join, Ref

def get_bucket_name_variable(environment_parameter, uuid_parameter):
  return Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])

def get_default_queue_name_variable(environment_parameter):
  return Join('-', [Ref(environment_parameter), 'default'])

def get_notifications_queue_name_variable(environment_parameter):
  return Join('-', [Ref(environment_parameter), 'notifications'])

def get_search_queue_name_variable(environment_parameter):
  return Join('-', [Ref(environment_parameter), 'search'])

def get_ci_user_name_variable(environment_parameter):
  return Join('-', ['ci-api', Ref(environment_parameter)])

def get_api_user_name_variable(environment_parameter):
  return Join('-', ['api', Ref(environment_parameter)])
