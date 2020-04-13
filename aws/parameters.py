from troposphere import Parameter

def get_uuid_parameter(template, uuid):
  return template.add_parameter(
    Parameter(
      'Uuid',
      Type='String',
      Default=uuid,
      Description='The unique ID for this stack.',
      MinLength='36',
      MaxLength='36'
    )
  )

def get_environment_parameter(template):
  return template.add_parameter(
    Parameter(
      'Environment',
      Type='String',
      Description='The environment this stack is for (e.g. production or staging).',
      MinLength='1'
    )
  )
