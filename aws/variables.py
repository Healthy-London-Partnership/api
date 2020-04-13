from troposphere import Join, Ref


def get_default_queue_name_variable(environment_parameter, uuid_parameter):
    return Join('-', ['default', Ref(environment_parameter), Ref(uuid_parameter)])


def get_notifications_queue_name_variable(environment_parameter, uuid_parameter):
    return Join('-', ['notifications', Ref(environment_parameter), Ref(uuid_parameter)])


def get_search_queue_name_variable(environment_parameter, uuid_parameter):
    return Join('-', ['search', Ref(environment_parameter), Ref(uuid_parameter)])


def get_uploads_bucket_name_variable(environment_parameter, uuid_parameter):
    return Join('-', ['uploads', Ref(environment_parameter), Ref(uuid_parameter)])


def get_api_launch_template_name_variable(environment_parameter):
    return Join('-', ['api-launch-template', Ref(environment_parameter)])


def get_docker_repository_name_variable(environment_parameter, uuid_parameter):
    return Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])


def get_api_log_group_name_variable(environment_parameter):
    return Join('-', ['api', Ref(environment_parameter)])


def get_queue_worker_log_group_name_variable(environment_parameter):
    return Join('-', ['queue-worker', Ref(environment_parameter)])


def get_scheduler_log_group_name_variable(environment_parameter):
    return Join('-', ['scheduler', Ref(environment_parameter)])


def get_api_task_definition_family_variable(environment_parameter):
    return Join('-', ['api', Ref(environment_parameter)])


def get_queue_worker_task_definition_family_variable(environment_parameter):
    return Join('-', ['queue-worker', Ref(environment_parameter)])


def get_scheduler_task_definition_family_variable(environment_parameter):
    return Join('-', ['scheduler', Ref(environment_parameter)])


def get_api_user_name_variable(environment_parameter):
    return Join('-', ['api', Ref(environment_parameter)])


def get_ci_user_name_variable(environment_parameter):
    return Join('-', ['ci', Ref(environment_parameter)])


def get_database_name_variable():
    return 'the_leeds_repo'


def get_database_username_variable():
    return 'the_leeds_repo'


def get_elasticsearch_domain_name_variable(environment_parameter):
    return Join('-', ['search', Ref(environment_parameter)])