from troposphere import Output, Ref

def get_bucket_name_output(template, bucket_name_variable):
  return template.add_output(
    Output(
      'BucketName',
      Description='The S3 bucket name',
      Value=bucket_name_variable
    )
  )

def get_default_queue_output(template, default_queue_resource):
  return template.add_output(
    Output(
      'DefaultQueue',
      Description='The URI of the default queue',
      Value=Ref(default_queue_resource)
    )
  )

def get_notifications_queue_output(template, notifications_queue_resource):
  return template.add_output(
    Output(
      'NotificationsQueue',
      Description='The URI of the notifications queue',
      Value=Ref(notifications_queue_resource)
    )
  )

def get_search_queue_output(template, search_queue_resource):
  return template.add_output(
    Output(
      'SearchQueue',
      Description='The URI of the search queue',
      Value=Ref(search_queue_resource)
    )
  )
