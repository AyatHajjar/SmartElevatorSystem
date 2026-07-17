

#include "main.h"


/* USER CODE BEGIN Includes */

#include <stdio.h>

#include <string.h>

/* USER CODE END Includes */


CAN_HandleTypeDef hcan;

UART_HandleTypeDef huart2;


/* USER CODE BEGIN PD */

#define SC_ID 0x100

#define EC_ID 0x101

#define CC_ID 0x200

#define F1_ID 0x201

#define F2_ID 0x202

#define F3_ID 0x203



#define NODE_ID CC_ID

/* CC_ID / F1_ID / F2_ID / F3_ID */


#define CC_REQ_FLOOR_1 0x01

#define CC_REQ_FLOOR_2 0x02

#define CC_REQ_FLOOR_3 0x03


#define EC_MOVING 0x04

#define FLOOR_1_STATUS 0x05

#define FLOOR_2_STATUS 0x06

#define FLOOR_3_STATUS 0x07


#define NO_BUTTON_PRESSED 0

#define BLUE_BUTTON_PRESSED 1

/* USER CODE END PD */


/* USER CODE BEGIN PV */

CAN_TxHeaderTypeDef TxHeader;

CAN_RxHeaderTypeDef RxHeader;


uint8_t TxData[8];

uint8_t RxData[8];


uint32_t TxMailbox;

volatile uint8_t last_ec_status = 0xFF;

volatile uint8_t BUTTON = NO_BUTTON_PRESSED;

volatile uint8_t elevator_busy = 0;

volatile uint8_t requested_floor = 0;


#define CAN_RX_QUEUE_SIZE 16


typedef struct

{

 CAN_RxHeaderTypeDef header;

 uint8_t data[8];

} CAN_RxMessage_t;


volatile CAN_RxMessage_t canRxQueue[CAN_RX_QUEUE_SIZE];

volatile uint8_t canRxHead = 0;

volatile uint8_t canRxTail = 0;

volatile uint8_t canRxOverflow = 0;

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


static void LEDs_AllOff(void)

{

 HAL_GPIO_WritePin(GPIOA,

 GPIO_PIN_4 | GPIO_PIN_6 | GPIO_PIN_7,

 GPIO_PIN_RESET);

}


static void Process_CAN_Message(CAN_RxHeaderTypeDef *header, uint8_t *data)

{

 char rxMsg[100];


 /* Ignore invalid CAN messages */

 if (header->IDE != CAN_ID_STD ||

 header->RTR != CAN_RTR_DATA ||

 header->DLC == 0)

 {

 return;

 }


 /*

 * EC = 0x101

 * Process every message, but print only when status changes.

 */

 if (header->StdId == EC_ID)

 {

 uint8_t status_changed = (data[0] != last_ec_status);


 if (status_changed)

 {

 sprintf(rxMsg,

 "Received CAN ID: 0x%03lX Data: 0x%02X\r\n",

 header->StdId,

 data[0]);


 UART_Print(rxMsg);

 }


 if (data[0] == EC_MOVING)

 {

 elevator_busy = 1;


 if (status_changed)

 {

 UART_Print("Action: EC_MOVING received\r\n");

 }

 }

 else if (data[0] == FLOOR_1_STATUS)

 {

 LEDs_AllOff();

 elevator_busy = 0;

 requested_floor = 0;


 if (status_changed)

 {

 UART_Print("Action: EC_STATUS_FLOOR_1 received\r\n");

 }

 }

 else if (data[0] == FLOOR_2_STATUS)

 {

 LEDs_AllOff();

 elevator_busy = 0;

 requested_floor = 0;


 if (status_changed)

 {

 UART_Print("Action: EC_STATUS_FLOOR_2 received\r\n");

 }

 }

 else if (data[0] == FLOOR_3_STATUS)

 {

 LEDs_AllOff();

 elevator_busy = 0;

 requested_floor = 0;


 if (status_changed)

 {

 UART_Print("Action: EC_STATUS_FLOOR_3 received\r\n");

 }

 }


 last_ec_status = data[0];

 return;

 }


 /*

 * Print every non-EC message.

 * Therefore SC / Raspberry Pi ID 0x100 is always displayed.

 */

 sprintf(rxMsg,

 "Received CAN ID: 0x%03lX Data: 0x%02X\r\n",

 header->StdId,

 data[0]);


 UART_Print(rxMsg);


 if (header->StdId == F1_ID)

 {

 UART_Print("Action: FLOOR_1_REQUEST received\r\n");

 }

 else if (header->StdId == F2_ID)

 {

 UART_Print("Action: FLOOR_2_REQUEST received\r\n");

 }

 else if (header->StdId == F3_ID)

 {

 UART_Print("Action: FLOOR_3_REQUEST received\r\n");

 }

 else if (header->StdId == CC_ID)

 {

 if (data[0] == CC_REQ_FLOOR_1)

 UART_Print("Action: CC_FLOOR_1_REQUEST received\r\n");


 else if (data[0] == CC_REQ_FLOOR_2)

 UART_Print("Action: CC_FLOOR_2_REQUEST received\r\n");


 else if (data[0] == CC_REQ_FLOOR_3)

 UART_Print("Action: CC_FLOOR_3_REQUEST received\r\n");

 }

 else if (header->StdId == SC_ID)

 {

 if (data[0] == FLOOR_1_STATUS)

 UART_Print("Action: SC_COMMAND_GO_TO_FLOOR_1 received\r\n");


 else if (data[0] == FLOOR_2_STATUS)

 UART_Print("Action: SC_COMMAND_GO_TO_FLOOR_2 received\r\n");


 else if (data[0] == FLOOR_3_STATUS)

 UART_Print("Action: SC_COMMAND_GO_TO_FLOOR_3 received\r\n");

 }

}





/* USER CODE END 0 */


int main(void)

{

 HAL_Init();

 SystemClock_Config();


 MX_GPIO_Init();

 MX_USART2_UART_Init();

 MX_CAN_Init();


 UART_Print("CAN Controller Started\r\n");

 UART_Print("Ready\r\n");


 while (1)

 {

 while (canRxTail != canRxHead)

 {

 CAN_RxHeaderTypeDef localHeader;

 uint8_t localData[8];

 uint8_t i;


 __disable_irq();

 localHeader = canRxQueue[canRxTail].header;

 for (i = 0; i < 8; i++)

 localData[i] = canRxQueue[canRxTail].data[i];

 canRxTail = (uint8_t)((canRxTail + 1U) % CAN_RX_QUEUE_SIZE);

 __enable_irq();


 Process_CAN_Message(&localHeader, localData);

 }


 if (canRxOverflow)

 {

 canRxOverflow = 0;

 UART_Print("Warning: CAN receive queue overflow\r\n");

 }


 if (BUTTON == BLUE_BUTTON_PRESSED)

 {

 char txMsg[100];


 TxHeader.StdId = NODE_ID;


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


 BUTTON = NO_BUTTON_PRESSED;

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

 Error_Handler();


 RCC_ClkInitStruct.ClockType = RCC_CLOCKTYPE_HCLK | RCC_CLOCKTYPE_SYSCLK |

 RCC_CLOCKTYPE_PCLK1 | RCC_CLOCKTYPE_PCLK2;

 RCC_ClkInitStruct.SYSCLKSource = RCC_SYSCLKSOURCE_PLLCLK;

 RCC_ClkInitStruct.AHBCLKDivider = RCC_SYSCLK_DIV1;

 RCC_ClkInitStruct.APB1CLKDivider = RCC_HCLK_DIV2;

 RCC_ClkInitStruct.APB2CLKDivider = RCC_HCLK_DIV1;


 if (HAL_RCC_ClockConfig(&RCC_ClkInitStruct, FLASH_LATENCY_2) != HAL_OK)

 Error_Handler();


 PeriphClkInit.PeriphClockSelection = RCC_PERIPHCLK_USART2;

 PeriphClkInit.Usart2ClockSelection = RCC_USART2CLKSOURCE_PCLK1;


 if (HAL_RCCEx_PeriphCLKConfig(&PeriphClkInit) != HAL_OK)

 Error_Handler();

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

 Error_Handler();


 CAN_FilterTypeDef filter;


 filter.FilterBank = 0;

 filter.FilterMode = CAN_FILTERMODE_IDMASK;

 filter.FilterScale = CAN_FILTERSCALE_32BIT;

 filter.FilterIdHigh = (0x100 << 5);

 filter.FilterIdLow = 0x0000;

 filter.FilterMaskIdHigh = (0x7FE << 5);

 filter.FilterMaskIdLow = 0x0000;

 filter.FilterFIFOAssignment = CAN_FILTER_FIFO0;

 filter.FilterActivation = ENABLE;

 filter.SlaveStartFilterBank = 14;


 if (HAL_CAN_ConfigFilter(&hcan, &filter) != HAL_OK)

 Error_Handler();


 filter.FilterBank = 1;

 filter.FilterMode = CAN_FILTERMODE_IDMASK;

 filter.FilterScale = CAN_FILTERSCALE_32BIT;

 filter.FilterIdHigh = (0x200 << 5);

 filter.FilterIdLow = 0x0000;

 filter.FilterMaskIdHigh = (0x7FC << 5);

 filter.FilterMaskIdLow = 0x0000;

 filter.FilterFIFOAssignment = CAN_FILTER_FIFO0;

 filter.FilterActivation = ENABLE;

 filter.SlaveStartFilterBank = 14;


 if (HAL_CAN_ConfigFilter(&hcan, &filter) != HAL_OK)

 Error_Handler();


 if (HAL_CAN_Start(&hcan) != HAL_OK)

 Error_Handler();


 if (HAL_CAN_ActivateNotification(&hcan, CAN_IT_RX_FIFO0_MSG_PENDING) != HAL_OK)

 Error_Handler();


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

 Error_Handler();

}


static void MX_GPIO_Init(void)

{

 GPIO_InitTypeDef GPIO_InitStruct = {0};


 __HAL_RCC_GPIOC_CLK_ENABLE();

 __HAL_RCC_GPIOF_CLK_ENABLE();

 __HAL_RCC_GPIOA_CLK_ENABLE();

 __HAL_RCC_GPIOB_CLK_ENABLE();


 GPIO_InitStruct.Pin = GPIO_PIN_12 | GPIO_PIN_14 | GPIO_PIN_15;

 GPIO_InitStruct.Mode = GPIO_MODE_IT_FALLING;

 GPIO_InitStruct.Pull = GPIO_PULLUP;

 HAL_GPIO_Init(GPIOB, &GPIO_InitStruct);


 GPIO_InitStruct.Pin = GPIO_PIN_4 | GPIO_PIN_6 | GPIO_PIN_7;

 GPIO_InitStruct.Mode = GPIO_MODE_OUTPUT_PP;

 GPIO_InitStruct.Pull = GPIO_NOPULL;

 GPIO_InitStruct.Speed = GPIO_SPEED_FREQ_LOW;

 HAL_GPIO_Init(GPIOA, &GPIO_InitStruct);


 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_4 | GPIO_PIN_6 | GPIO_PIN_7, GPIO_PIN_RESET);


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


void HAL_CAN_RxFifo0MsgPendingCallback(CAN_HandleTypeDef *hcan)

{

 while (HAL_CAN_GetRxFifoFillLevel(hcan, CAN_RX_FIFO0) > 0)

 {

 CAN_RxHeaderTypeDef tempHeader;

 uint8_t tempData[8];

 uint8_t nextHead;

 uint8_t i;


 if (HAL_CAN_GetRxMessage(

 hcan,

 CAN_RX_FIFO0,

 &tempHeader,

 tempData) != HAL_OK)

 {

 return;

 }


 nextHead = (uint8_t)((canRxHead + 1U) % CAN_RX_QUEUE_SIZE);


 if (nextHead == canRxTail)

 {

 canRxOverflow = 1;

 return;

 }


 canRxQueue[canRxHead].header = tempHeader;


 for (i = 0; i < 8; i++)

 {

 canRxQueue[canRxHead].data[i] = tempData[i];

 }


 canRxHead = nextHead;

 }

}



void HAL_GPIO_EXTI_Callback(uint16_t GPIO_Pin)

{



 if (NODE_ID == CC_ID)

 {

 if(GPIO_Pin == GPIO_PIN_12)

 {

 UART_Print("CC Floor 1 Button Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_4, GPIO_PIN_SET);

 TxData[0] = CC_REQ_FLOOR_1;

 requested_floor = 1;

 BUTTON = BLUE_BUTTON_PRESSED;

 }

 else if(GPIO_Pin == GPIO_PIN_14)

 {

 UART_Print("CC Floor 2 Button Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_6, GPIO_PIN_SET);

 TxData[0] = CC_REQ_FLOOR_2;

 requested_floor = 2;

 BUTTON = BLUE_BUTTON_PRESSED;

 }

 else if(GPIO_Pin == GPIO_PIN_15)

 {

 UART_Print("CC Floor 3 Button Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_7, GPIO_PIN_SET);

 TxData[0] = CC_REQ_FLOOR_3;

 requested_floor = 3;

 BUTTON = BLUE_BUTTON_PRESSED;

 }

 }

 else if (NODE_ID == F1_ID || NODE_ID == F2_ID || NODE_ID == F3_ID)

 {

 if(GPIO_Pin == GPIO_PIN_12 || GPIO_Pin == GPIO_PIN_14 || GPIO_Pin == GPIO_PIN_15)

 {

 if (NODE_ID == F1_ID)

 {

 UART_Print("Floor 1 Controller Request Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_4, GPIO_PIN_SET);

 requested_floor = 1;

 }

 else if (NODE_ID == F2_ID)

 {

 UART_Print("Floor 2 Controller Request Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_6, GPIO_PIN_SET);

 requested_floor = 2;

 }

 else if (NODE_ID == F3_ID)

 {

 UART_Print("Floor 3 Controller Request Pressed\r\n");

 HAL_GPIO_WritePin(GPIOA, GPIO_PIN_7, GPIO_PIN_SET);

 requested_floor = 3;

 }


 TxData[0] = 0x01;

 BUTTON = BLUE_BUTTON_PRESSED;

 }

 }

}


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

