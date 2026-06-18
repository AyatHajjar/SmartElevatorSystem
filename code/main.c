/* USER CODE BEGIN Header */
/**
 ******************************************************************************
 * @file           : main.c
 * @brief          : Main program body
 ******************************************************************************
 */
/* USER CODE END Header */

#include "main.h"

/* USER CODE BEGIN Includes */
#include <stdio.h>
#include <string.h>
/* USER CODE END Includes */

/* Private variables ---------------------------------------------------------*/
CAN_HandleTypeDef hcan;
UART_HandleTypeDef huart2;

/* USER CODE BEGIN PD */
#define SC_ID 0X100
#define EC_ID 0X101
#define CC_ID 0X200
#define F1_ID 0X201
#define F2_ID 0X202
#define F3_ID 0X203
#define NODE_ID F1_ID
/* THIS ID I HAVE TO CHANGE IT IN EACH FLOOR . F1 F2 F3 CC*/
#define GO_TO_FLOOR_1       0x05
#define GO_TO_FLOOR_2       0x06
#define GO_TO_FLOOR_3       0x07
#define NO_BUTTON_PRESSED   0
#define BLUE_BUTTON_PRESSED 1
/* USER CODE END PD */

/* USER CODE BEGIN PV */
CAN_TxHeaderTypeDef TxHeader;
CAN_RxHeaderTypeDef RxHeader;

uint8_t TxData[8];
uint8_t RxData[8];

uint32_t TxMailbox;

uint8_t msg = GO_TO_FLOOR_1;
uint8_t BUTTON = NO_BUTTON_PRESSED;
uint8_t i;
/* USER CODE END PV */

void SystemClock_Config(void);
static void MX_GPIO_Init(void);
static void MX_USART2_UART_Init(void);
static void MX_CAN_Init(void);

/* USER CODE BEGIN 0 */
void UART_Print(char *msg)
{
    HAL_UART_Transmit(&huart2, (uint8_t*)msg, strlen(msg), 100);
}
/* USER CODE END 0 */

int main(void)
{
    HAL_Init();
    SystemClock_Config();

    MX_GPIO_Init();
    MX_USART2_UART_Init();
    MX_CAN_Init();

    /* USER CODE BEGIN 2 */
    UART_Print("CAN Controller Started\r\n");
    UART_Print("Press Blue Button\r\n");
    /* USER CODE END 2 */

    while (1)
    {
    	/* Receive */
    	if (RxData[0] == GO_TO_FLOOR_1)
    	{
    	    UART_Print("Action: GO_TO_FLOOR_1 received\r\n");
    	    HAL_GPIO_TogglePin(GPIOA, GPIO_PIN_5);
    	}
    	else if (RxData[0] == GO_TO_FLOOR_2)
    	{
    	    UART_Print("Action: GO_TO_FLOOR_2 received\r\n");
    	    HAL_GPIO_TogglePin(GPIOA, GPIO_PIN_5);
    	}
    	else if (RxData[0] == GO_TO_FLOOR_3)
    	{
    	    UART_Print("Action: GO_TO_FLOOR_3 received\r\n");
    	    HAL_GPIO_TogglePin(GPIOA, GPIO_PIN_5);
    	}

    	if (RxData[0] == GO_TO_FLOOR_1 ||
    	    RxData[0] == GO_TO_FLOOR_2 ||
    	    RxData[0] == GO_TO_FLOOR_3)
    	{
    	    HAL_Delay(100);

    	    for (i = 0; i < 8; i++)
    	    {
    	        RxData[i] = 0x00;
    	    }
    	}
        /* Transmit */
        if (BUTTON != NO_BUTTON_PRESSED)
        {
            if (BUTTON == BLUE_BUTTON_PRESSED)
            {
                char txMsg[100];

                HAL_GPIO_TogglePin(GPIOA, GPIO_PIN_5);
                HAL_Delay(300);

                TxData[0] = msg;

                if (HAL_CAN_AddTxMessage(&hcan, &TxHeader, TxData, &TxMailbox) != HAL_OK)
                {
                    UART_Print("CAN Send Error\r\n");
                    Error_Handler();
                }

                sprintf(txMsg,
                        "Sent CAN ID: 0x%03lX Data: 0x%02X\r\n",
                        TxHeader.StdId,
                        TxData[0]);

                UART_Print(txMsg);

                HAL_GPIO_TogglePin(GPIOA, GPIO_PIN_5);

                BUTTON = NO_BUTTON_PRESSED;
            }
        }
    }
}

void SystemClock_Config(void)
{
    RCC_OscInitTypeDef RCC_OscInitStruct = {0};
    RCC_ClkInitTypeDef RCC_ClkInitStruct = {0};
    RCC_PeriphCLKInitTypeDef PeriphClkInit = {0};

    RCC_OscInitStruct.OscillatorType = RCC_OSCILLATORTYPE_HSI;
    RCC_OscInitStruct.HSIState = RCC_HSI_ON;
    RCC_OscInitStruct.HSICalibrationValue = RCC_HSICALIBRATION_DEFAULT;
    RCC_OscInitStruct.PLL.PLLState = RCC_PLL_ON;
    RCC_OscInitStruct.PLL.PLLSource = RCC_PLLSOURCE_HSI;
    RCC_OscInitStruct.PLL.PLLMUL = RCC_PLL_MUL9;
    RCC_OscInitStruct.PLL.PREDIV = RCC_PREDIV_DIV1;

    if (HAL_RCC_OscConfig(&RCC_OscInitStruct) != HAL_OK)
    {
        Error_Handler();
    }

    RCC_ClkInitStruct.ClockType = RCC_CLOCKTYPE_HCLK | RCC_CLOCKTYPE_SYSCLK |
                                  RCC_CLOCKTYPE_PCLK1 | RCC_CLOCKTYPE_PCLK2;

    RCC_ClkInitStruct.SYSCLKSource = RCC_SYSCLKSOURCE_PLLCLK;
    RCC_ClkInitStruct.AHBCLKDivider = RCC_SYSCLK_DIV1;
    RCC_ClkInitStruct.APB1CLKDivider = RCC_HCLK_DIV2;
    RCC_ClkInitStruct.APB2CLKDivider = RCC_HCLK_DIV1;

    if (HAL_RCC_ClockConfig(&RCC_ClkInitStruct, FLASH_LATENCY_2) != HAL_OK)
    {
        Error_Handler();
    }

    PeriphClkInit.PeriphClockSelection = RCC_PERIPHCLK_USART2;
    PeriphClkInit.Usart2ClockSelection = RCC_USART2CLKSOURCE_PCLK1;

    if (HAL_RCCEx_PeriphCLKConfig(&PeriphClkInit) != HAL_OK)
    {
        Error_Handler();
    }
}

static void MX_CAN_Init(void)
{
    hcan.Instance = CAN;
    hcan.Init.Prescaler = 32;
    hcan.Init.Mode = CAN_MODE_NORMAL;
    hcan.Init.SyncJumpWidth = CAN_SJW_1TQ;
    hcan.Init.TimeSeg1 = CAN_BS1_4TQ;
    hcan.Init.TimeSeg2 = CAN_BS2_4TQ;
    hcan.Init.TimeTriggeredMode = DISABLE;
    hcan.Init.AutoBusOff = DISABLE;
    hcan.Init.AutoWakeUp = DISABLE;
    hcan.Init.AutoRetransmission = DISABLE;
    hcan.Init.ReceiveFifoLocked = DISABLE;
    hcan.Init.TransmitFifoPriority = DISABLE;

    if (HAL_CAN_Init(&hcan) != HAL_OK)
    {
        Error_Handler();
    }

    CAN_FilterTypeDef filter;

    filter.FilterBank = 0;
    filter.FilterIdHigh = 0x0100 << 5;
    filter.FilterIdLow = 0x0000;
    filter.FilterMaskIdHigh = 0xFFC << 5;
    filter.FilterMaskIdLow = 0x0000;
    filter.FilterFIFOAssignment = CAN_FILTER_FIFO0;
    filter.FilterMode = CAN_FILTERMODE_IDMASK;
    filter.FilterScale = CAN_FILTERSCALE_32BIT;
    filter.FilterActivation = ENABLE;
    filter.SlaveStartFilterBank = 0;

    if (HAL_CAN_ConfigFilter(&hcan, &filter) != HAL_OK)
    {
        Error_Handler();
    }

    if (HAL_CAN_Start(&hcan) != HAL_OK)
    {
        Error_Handler();
    }

    if (HAL_CAN_ActivateNotification(&hcan, CAN_IT_RX_FIFO0_MSG_PENDING) != HAL_OK)
    {
        Error_Handler();
    }

    TxHeader.IDE = CAN_ID_STD;
    TxHeader.ExtId = 0x00;
    TxHeader.StdId = NODE_ID;
    TxHeader.RTR = CAN_RTR_DATA;
    TxHeader.DLC = 1;
    TxHeader.TransmitGlobalTime = DISABLE;
}

static void MX_USART2_UART_Init(void)
{
    huart2.Instance = USART2;
    huart2.Init.BaudRate = 38400;
    huart2.Init.WordLength = UART_WORDLENGTH_8B;
    huart2.Init.StopBits = UART_STOPBITS_1;
    huart2.Init.Parity = UART_PARITY_NONE;
    huart2.Init.Mode = UART_MODE_TX_RX;
    huart2.Init.HwFlowCtl = UART_HWCONTROL_NONE;
    huart2.Init.OverSampling = UART_OVERSAMPLING_16;
    huart2.Init.OneBitSampling = UART_ONE_BIT_SAMPLE_DISABLE;
    huart2.AdvancedInit.AdvFeatureInit = UART_ADVFEATURE_NO_INIT;

    if (HAL_UART_Init(&huart2) != HAL_OK)
    {
        Error_Handler();
    }
}

static void MX_GPIO_Init(void)
{
    GPIO_InitTypeDef GPIO_InitStruct = {0};

    __HAL_RCC_GPIOC_CLK_ENABLE();
    __HAL_RCC_GPIOF_CLK_ENABLE();
    __HAL_RCC_GPIOA_CLK_ENABLE();
    __HAL_RCC_GPIOB_CLK_ENABLE();

    HAL_GPIO_WritePin(LD2_GPIO_Port, LD2_Pin, GPIO_PIN_RESET);

    GPIO_InitStruct.Pin = B1_Pin;
    GPIO_InitStruct.Mode = GPIO_MODE_IT_FALLING;
    GPIO_InitStruct.Pull = GPIO_NOPULL;
    HAL_GPIO_Init(B1_GPIO_Port, &GPIO_InitStruct);

    GPIO_InitStruct.Pin = LD2_Pin;
    GPIO_InitStruct.Mode = GPIO_MODE_OUTPUT_PP;
    GPIO_InitStruct.Pull = GPIO_NOPULL;
    GPIO_InitStruct.Speed = GPIO_SPEED_FREQ_LOW;
    HAL_GPIO_Init(LD2_GPIO_Port, &GPIO_InitStruct);

    HAL_NVIC_SetPriority(EXTI15_10_IRQn, 0, 0);
    HAL_NVIC_EnableIRQ(EXTI15_10_IRQn);
}

/* USER CODE BEGIN 4 */

void HAL_CAN_RxFifo0MsgPendingCallback(CAN_HandleTypeDef *hcan)
{
    char rxMsg[100];

    if (HAL_CAN_GetRxMessage(hcan, CAN_RX_FIFO0, &RxHeader, RxData) != HAL_OK)
    {
        UART_Print("CAN Receive Error\r\n");
        Error_Handler();
    }

    sprintf(rxMsg,
            "Received CAN ID: 0x%03lX Data: 0x%02X\r\n",
            RxHeader.StdId,
            RxData[0]);

    UART_Print(rxMsg);
}

void HAL_GPIO_EXTI_Callback(uint16_t GPIO_Pin)
{
    if (GPIO_Pin == GPIO_PIN_13)
    {
        BUTTON = BLUE_BUTTON_PRESSED;
        UART_Print("Blue Button Pressed\r\n");
    }
}

/* USER CODE END 4 */

void Error_Handler(void)
{
    __disable_irq();

    while (1)
    {
    }
}

#ifdef USE_FULL_ASSERT
void assert_failed(uint8_t *file, uint32_t line)
{
}
#endif
