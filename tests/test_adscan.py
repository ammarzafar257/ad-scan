import time

from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from config import config

class TestADScan:

    def test_adscan_chrome(self):
        """
        Runs selenium tests against ADScan in Chrome
        You can toggle between staging and production on lines 27 and 28 with the crawl selection being at 60 and 61
        """

        # Below you can select which webdriver you would like to use.
        driver = webdriver.Chrome()
        # driver = webdriver.Edge()
        # driver = webdriver.FireFox()

        # this makes the window take up the full screen
        driver.maximize_window()

        # This is where you select which site to run the script on

        driver.get('https://staging-adscan.abledocs.com')
        # driver.get('https://adscan.abledocs.com')

        # Please toggle only one language or do not toggle any for system default.

        # This is the toggle for DA
        # driver.find_element(By.ID, 'la_da').click()

        # This is the toggle for DE
        # driver.find_element(By.ID, 'le_de').click()

        # This is the toggle for EN
        # driver.find_element(By.ID, 'le_en').click()

        # This is the toggle for ES
        # driver.find_element(By.ID, 'la_es').click()

        # This is the toggle for FR
        # driver.find_element(By.ID, 'la_fr').click()

        # This is the toggle for NL
        # driver.find_element(By.ID, 'la_nl').click()

        # This is the toggle for IT
        # driver.find_element(By.ID, 'la_it').click()
        time.sleep(3)
        driver.find_element(By.XPATH, '//*[@id="login-email"]').send_keys(config.adscan_user_email)
        driver.find_element(By.XPATH, '//*[@id="login-password"]').send_keys(config.adscan_user_password)
        driver.find_element(By.ID, 'loginbutton').click()

        # This sleep is to let the admin view load.
        time.sleep(1)

        driver.find_element(By.LINK_TEXT, '245').click() # Staging scan
        # driver.find_element(By.LINK_TEXT, '304').click()  # Production scan
        # This sleep is to allow the page to load graphics.
        time.sleep(2)

        driver.find_element(By.XPATH, '//*[@id="document-counter"]/div[2]/div[3]/p').click()
        driver.find_element(By.XPATH, '//*[@id="pro-exportCSV-btn"]').click()
        # This sleep is to give the csv file time to download
        time.sleep(8)

        driver.execute_script("window.open('');")
        driver.switch_to.window(driver.window_handles[1])
        driver.get("chrome://downloads/")
        time.sleep(1)
        driver.find_elements(By.XPATH, "//*[contains(text(), 'adscan-export')]")
        time.sleep(1)
        driver.switch_to.window(driver.window_handles[0])
        driver.find_element(By.ID, 'showallfiles').click()
        driver.find_element(By.ID, 'showcompfiles').click()
        driver.find_element(By.ID, 'shownoncompfiles').click()
        driver.find_element(By.ID, 'showuntaggedfiles').click()
        driver.find_element(By.ID, 'showoffsitefiles').click()
        driver.find_element(By.ID, 'showerrorfiles').click()
        driver.find_element(By.ID, 'showallfiles').click()
        # This sleep is to allow the table to load.
        time.sleep(3)

        actions = ActionChains(driver)

        # this is where it clicks the name column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[1]'))
        actions.perform()
        elementName1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[3]/div/a').text
        assert elementName1 == 'PDFX-in-a-Nutshell.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[1]').click()
        elementName2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[3]/div/a').text
        assert elementName2 == 'clarin-in-the-low-countries.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[1]').click()
        elementName3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[3]/div/a').text
        assert elementName3 == 'writing-as-material-practice.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[1]').click()
        time.sleep(1)

        # this is the sorting for the name column

        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[3]').click()
        namefilter = driver.find_element(By.XPATH, '//*[@id="strui-grid-column2"]')
        namefilter.send_keys('clarin')
        driver.find_element(By.XPATH, '//*[@id="grid-column2-flmdlg"]/div[2]/button[1]').click()
        time.sleep(1)
        namefiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[3]/div').text
        assert namefiltertest == 'clarin-in-the-low-countries.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[3]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column2-flmdlg"]/div[2]/button[2]').click()
        time.sleep(0.5)

        # this is where it clicks the UA index column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[1]/span'))
        actions.perform()
        elementUA1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[4]/div').text
        assert elementUA1 == '95.86'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[1]/span').click()
        elementUA2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[4]').text
        assert elementUA2 == '47.90'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[1]/span').click()
        elementUA3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[4]/div').text
        assert elementUA3 == '96.78'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[1]/span').click()
        time.sleep(1)

        # this is the sorting for UA index

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[6]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[3]').click()
        time.sleep(0.5)
        uaindexfilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column3"]')
        time.sleep(0.5)
        uaindexfilter.send_keys('62.91')
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="grid-column3-flmdlg"]/div[2]/button[1]').click()
        uaindexfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[4]/div').text
        assert uaindexfiltertest == '62.91'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[4]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column3-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks the URL column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[1]'))
        actions.perform()
        elementURL1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[5]').text
        assert elementURL1 == 'https://nice-smoke-067c5f210.azurestaticapps.net/documents/PDFX-in-a-Nutshell.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[1]').click()
        elementURL2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[5]').text
        assert elementURL2 == 'https://crawler-test.com/pdf_open_parameters.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[1]').click()
        elementURL3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[5]').text
        assert elementURL3 == 'https://nice-smoke-067c5f210.azurestaticapps.net/documents/writing-as-material-practice.pdf'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[1]').click()
        time.sleep(1)

        # this is the sorting for the URL column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[7]/div'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column4-flmdlg"]/div[2]/button[1]').click()
        urlfilter = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[5]').text
        assert urlfilter == 'https://nice-smoke-067c5f210.azurestaticapps.net/documents/PDFX-in-a-Nutshell.pdf'
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[5]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column4-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks the Page Count column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[1]/span'))
        actions.perform()
        elementPageCount1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[6]').text
        assert elementPageCount1 == '17'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[1]/span').click()
        elementPageCount2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[6]').text
        assert elementPageCount2 == '17'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[1]/span').click()
        elementPageCount3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[6]').text
        assert elementPageCount3 == '412'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[1]/span').click()
        time.sleep(1)

        # this is the sorting for the Page Count column

        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[3]').click()
        pagecountfilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column5"]')
        pagecountfilter.send_keys('17')
        driver.find_element(By.XPATH, '//*[@id="grid-column5-flmdlg"]/div[2]/button[1]').click()
        pagecountfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[6]').text
        time.sleep(1)
        assert pagecountfiltertest == '17'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[6]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column5-flmdlg"]/div[2]/button[2]').click()
        time.sleep(0.5)

        # this is where it clicks the Tagged column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[1]/span'))
        actions.perform()
        elementTagged1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[7]').text
        assert elementTagged1 == 'Yes'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[1]/span').click()
        elementTagged2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[7]/div').text
        assert elementTagged2 == 'No'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[1]/span').click()
        elementTagged3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[7]/div').text
        assert elementTagged3 == 'Yes'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[1]/span').click()
        time.sleep(1)

        # this is the sorting for the Tagged column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column6-flmenu"]/div[2]/span').click()
        taggedfilter = driver.find_element(By.XPATH, '//*[@id="grid-column6-flmenu"]/div[2]/span')
        time.sleep(0.5)
        taggedfilter.send_keys('y')
        time.sleep(0.5)
        taggedfilter.send_keys(Keys.RETURN)
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="grid-column6-flmdlg"]/div[2]/button[1]').click()
        taggedfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[7]/div').text
        assert taggedfiltertest == 'Yes'
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[7]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column6-flmdlg"]/div[2]/button[2]').click()
        time.sleep(0.5)

        # this is where it clicks on the Title column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[1]/span'))
        actions.perform()
        elementTitle1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[8]').text
        assert elementTitle1 == 'PDF/X in a Nutshell'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[1]/span').click()
        elementTitle2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[8]').text
        assert elementTitle2 == 'Dangerous Science: Science Policy and Risk Analysis for Scientists and Engineers'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[1]/span').click()
        elementTitle3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[8]').text
        assert elementTitle3 == 'Variance in Approach Toward a ‘Sustainable’ Coffee Industry in Costa Rica: Perspectives from Within; Lessons and Insights'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[1]/span').click()

        # this is the sorting for the Title column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="strui-grid-column7"]').click()
        titlefilter = driver.find_element(By.XPATH, '//*[@id="strui-grid-column7"]')
        time.sleep(0.5)
        titlefilter.send_keys('P')
        time.sleep(0.5)
        titlefilter.send_keys(Keys.RETURN)
        time.sleep(0.5)
        titlefiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[8]').text
        assert titlefiltertest == 'PDF/A in a Nutshell'
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[8]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column7-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Application column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[1]'))
        actions.perform()
        elementApp1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]').text
        assert elementApp1 == 'Adobe InDesign CS6 (Macintosh)'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[1]').click()
        elementApp2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]').text
        assert elementApp2 == 'Adobe InDesign 15.0 (Macintosh)'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[1]').click()
        elementApp3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]').text
        assert elementApp3 == 'PScript5.dll Version 5.2.2'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[1]').click()

        # this is the sorting for the Application column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[11]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro_SearchBox"]').click()
        appfilter = driver.find_element(By.XPATH, '//*[@id="files-table-pro_SearchBox"]')
        appfilter.send_keys('adobe')
        time.sleep(0.5)
        appfilter.send_keys(Keys.RETURN)
        appfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[9]').text
        assert appfiltertest == 'Adobe InDesign CS6 (Macintosh)'
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[9]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="files-table-prostring_excelDlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Producer column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[1]'))
        actions.perform()
        elementProducer1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[10]').text
        assert elementProducer1 == 'Adobe PDF Library 10.0.1'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[1]').click()
        elementProducer2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[10]').text
        assert elementProducer2 == 'Acrobat Distiller 9.0.0 (Windows)'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[1]').click()
        elementProducer3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[10]').text
        assert elementProducer3 == 'pdfTeX-1.40.14'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[1]').click()

        # this is the sorting for the Producer column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[12]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro_SearchBox"]').click()
        producerfilter = driver.find_element(By.XPATH, '//*[@id="files-table-pro_SearchBox"]')
        producerfilter.send_keys('acro')
        time.sleep(0.5)
        producerfilter.send_keys(Keys.RETURN)
        producerfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[10]').text
        assert producerfiltertest == 'Acrobat Distiller 9.0.0 (Windows)'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[10]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="files-table-prostring_excelDlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Created On column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[11]/div[1]'))
        actions.perform()
        elementCreated1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[11]').text
        assert elementCreated1 == '2017/05/09'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[11]/div[1]').click()
        elementCreated2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[11]').text
        assert elementCreated2 == '1969/12/31'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[11]/div[1]').click()
        elementCreated3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[11]').text
        assert elementCreated3 == '2020/01/28'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[11]/div[1]').click()

        # this is where it clicks on the Last Modified column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[12]/div[1]/span'))
        actions.perform()
        elementLastMod1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[12]').text
        assert elementLastMod1 == '2017/05/15'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[12]/div[1]/span').click()
        elementLastMod2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[12]').text
        assert elementLastMod2 == '1969/12/31'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[12]/div[1]/span').click()
        elementLastMod3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[12]').text
        assert elementLastMod3 == '2020/01/30'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[12]/div[1]/span').click()

        # this is where it clicks on the Langauge column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[1]/span'))
        actions.perform()
        elementLa1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[13]').text
        assert elementLa1 == 'de-DE'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[1]/span').click()
        elementLa2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[13]').text
        assert elementLa2 == 'de-DE'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[1]/span').click()
        elementLa3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[13]').text
        assert elementLa3 == 'en-US'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[1]/span').click()

        # this is the sorting for the Langauge column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[15]/div'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[3]').click()
        lafilter = driver.find_element(By.XPATH, '//*[@id="files-table-pro_SearchBox"]')
        lafilter.send_keys('d')
        time.sleep(0.5)
        lafilter.send_keys(Keys.RETURN)
        time.sleep(0.5)
        lafiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[13]').text
        assert lafiltertest == 'de-DE'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[13]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="files-table-prostring_excelDlg"]/div[2]/button[2]').click()

        # this is where it clicks on the File Size column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[1]'))
        actions.perform()
        elementFileSize1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[14]').text
        assert elementFileSize1 == '321706'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[1]').click()
        elementFileSize2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[14]').text
        assert elementFileSize2 == '321706'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[1]').click()
        elementFileSize3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[14]').text
        assert elementFileSize3 == '74948574'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[1]').click()

        # this is the sorting for the File Size column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[16]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[3]').click()
        filesizefilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column13"]')
        filesizefilter.send_keys('321706')
        time.sleep(0.5)
        filesizefilter.send_keys(Keys.RETURN)
        time.sleep(0.5)
        filesizefiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[14]').text
        assert filesizefiltertest == '321706'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[14]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column13-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Off Site column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[1]/span'))
        actions.perform()
        elementOffSite1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[15]/div').text
        assert elementOffSite1 == 'No'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[1]/span').click()
        elementOffSite2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[15]/div').text
        assert elementOffSite2 == 'No'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[1]/span').click()
        elementOffSite3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[15]/div').text
        assert elementOffSite3 == 'Yes'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[1]/span').click()

        # this is the sorting for the Off Site column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[17]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column14-flmenu"]/div[2]/span').click()
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="grid-column14-flmenu"]/div[2]/span').click()
        offsitefilter = driver.find_element(By.XPATH, '//*[@id="grid-column14-flmenu"]/div[2]/span')
        offsitefilter.send_keys('Yes')
        time.sleep(0.5)
        offsitefilter.send_keys(Keys.RETURN)
        driver.find_element(By.XPATH, '//*[@id="grid-column14-flmdlg"]/div[2]/button[1]').click()
        time.sleep(0.5)
        offsitefiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[15]/div').text
        assert offsitefiltertest == 'Yes'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[15]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column14-flmenu"]/div[2]/span').click()
        time.sleep(0.5)
        driver.find_element(By.XPATH, '//*[@id="grid-column14-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Passed column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[1]'))
        actions.perform()
        elementPassed1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[16]').text
        assert elementPassed1 == '73021'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[1]').click()
        elementPassed2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[16]').text
        assert elementPassed2 == '14409'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[1]').click()
        elementPassed3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[16]').text
        assert elementPassed3 == '1178809'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[1]').click()

        # this is the sorting for the Passed column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[3]').click()
        passedfilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column15"]')
        passedfilter.send_keys('73021')
        time.sleep(0.5)
        passedfilter.send_keys(Keys.RETURN)
        passedfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[16]').text
        assert passedfiltertest == '73021'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[16]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column15-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Warned column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[1]/span'))
        actions.perform()
        elementWarned1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[17]').text
        assert elementWarned1 == '6'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[1]/span').click()
        elementWarned2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[17]').text
        assert elementWarned2 == '0'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[1]/span').click()
        elementWarned3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[17]').text
        assert elementWarned3 == '427'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[1]/span').click()

        # this is the sorting for the Warned column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[3]').click()
        warnedfilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column16"]')
        warnedfilter.send_keys('22')
        driver.find_element(By.XPATH, '//*[@id="grid-column16-flmdlg"]/div[2]/button[1]').click()
        warnedfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[17]').text
        assert warnedfiltertest == '22'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[17]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column16-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on the Failed column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[1]/span'))
        actions.perform()
        elementFailed1 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]').text
        assert elementFailed1 == '8'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[1]/span').click()
        elementFailed2 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]').text
        assert elementFailed2 == '8'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[1]/span').click()
        elementFailed3 = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]').text
        assert elementFailed3 == '1309161'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[1]/span').click()

        # this is the sorting for the Failed column

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[1]/td[18]'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[3]').click()
        failedfilter = driver.find_element(By.XPATH, '//*[@id="numberui-grid-column17"]')
        failedfilter.send_keys('4776')
        time.sleep(0.5)
        failedfilter.send_keys(Keys.RETURN)
        failedfiltertest = driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr/td[18]').text
        assert failedfiltertest == '4776'
        driver.find_element(By.XPATH, '//*[@id="files-table-pro"]/div[3]/div/table/thead/tr/th[18]/div[3]').click()
        driver.find_element(By.XPATH, '//*[@id="grid-column17-flmdlg"]/div[2]/button[2]').click()

        # this is where it clicks on a document to enter document view

        actions.move_to_element(driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[9]/td[3]/div/a'))
        actions.perform()
        driver.find_element(By.XPATH, '//*[@id="files-table-pro_content_table"]/tbody/tr[9]/td[3]/div/a').click()

        # This sleep is to allow the document view to load.
        time.sleep(4)

        driver.find_element(By.XPATH, '//*[@id="next-page"]').click()
        driver.find_element(By.XPATH, '//*[@id="prev-page"]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '/html/body/main/div[3]/div[1]/table/tbody[1]/tr[3]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[53]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[54]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[53]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[1]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[3]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[56]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[57]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[56]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[97]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[98]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[97]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[5]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[3]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[53]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[54]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[53]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[6]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[5]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[6]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[5]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[56]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[57]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[56]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[8]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[26]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[27]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[26]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[54]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[55]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[54]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[9]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[3]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[4]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[3]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[5]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[6]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[5]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[7]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[8]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[7]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[10]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[1]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[3]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[2]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[4]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[5]/td').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[4]/td[1]').click()
        driver.find_element(By.XPATH, '//*[@id="errorsTable"]/tbody[11]/tr[1]/td[1]').click()
        driver.quit()
        assert True
