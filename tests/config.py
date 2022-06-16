import os


class Config:
    """
    Wrapper around environment variables that the PyTests require
    """
    @property
    def adscan_user_email(self) -> str:
        return os.environ.get('ADSCAN_USER_EMAIL', None)

    @property
    def adscan_user_password(self) -> str:
        return os.getenv('ADSCAN_USER_PASSWORD', None)


config = Config()
